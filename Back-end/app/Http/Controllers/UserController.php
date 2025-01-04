<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Departement;
use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class UserController extends Controller
{
    // Lister tous les utilisateurs
    public function list()
    {
        $users = Users::all();
        return response()->json($users);
    }

    // Créer un utilisateur
    public function create(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'], // Validation pour 9 chiffres            
            'adresse' => 'nullable|string',
            'photo' => 'nullable|string',
            'role' => 'required|in:employe,apprenant,admin,vigile',
            'departement_id' => 'nullable|string|exists:departements,_id',
            'cohorte_id' => 'nullable|string|exists:cohortes,_id',
            'cardID' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Générer le matricule
        $matricule = $this->generateMatricule($request->role);

        // Préparer les données
        $data = $request->all();
        $data['matricule'] = $matricule;

        // Si le rôle est employe, affecter le département et nullifier la cohorte
        if ($request->role === 'employe') {
            $data['cohorte_id'] = null;
        }

        // Si le rôle est apprenant, affecter la cohorte et nullifier le département
        if ($request->role === 'apprenant') {
            $data['departement_id'] = null;
        }

        // Créer l'utilisateur
        $user = Users::create($data);

        return response()->json($user, 201);
    }

    // Créer un utilisateur à partir d'un département
    public function createFromDepartement(Request $request, $departement_id)
    {
        $departement = Departement::find($departement_id);
        if (!$departement) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }
        $request->merge(['departement_id' => $departement_id, 'role' => 'employe']);
        return $this->create($request);
    }

    // Créer un utilisateur à partir d'une cohorte
    public function createFromCohorte(Request $request, $cohorte_id)
    {
        $cohorte = Cohorte::find($cohorte_id);
        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }
        $request->merge(['cohorte_id' => $cohorte_id, 'role' => 'apprenant']);
        return $this->create($request);
    }

    // Voir un utilisateur spécifique
    public function view($id)
    {
        $user = Users::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
        return response()->json($user);
    }

    // Mettre à jour un utilisateur
    public function update(Request $request, $id)
    {
        $user = Users::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'telephone' => 'sometimes|required|string|max:20',
            'adresse' => 'nullable|string',
            'photo' => 'nullable|string',
            'role' => 'sometimes|required|in:employe,apprenant',
            'departement_id' => 'nullable|string|exists:departements,_id',
            'cohorte_id' => 'nullable|string|exists:cohortes,_id',
            'cardID' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Mettre à jour l'utilisateur
        $user->update($request->all());

        return response()->json($user, 200);
    }

    // Supprimer un utilisateur
    public function delete($id)
    {
        $user = Users::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
        $user->delete();
        return response()->json(null, 204);
    }

    // Générer un matricule
    private function generateMatricule($role)
    {
        $prefix = ($role === 'employe') ? 'EMP' : 'APP';
        $lastUser = Users::where('role', $role)->orderBy('created_at', 'desc')->first();
        $lastNumber = $lastUser ? (int)substr($lastUser->matricule, 3) : 0;
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        return $prefix . $newNumber;
    }

    // Lister les utilisateurs par département
    public function listByDepartement($departement_id)
    {
        $departement = Departement::find($departement_id);
        if (!$departement) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }

        $users = Users::where('departement_id', $departement_id)->get();
        return response()->json($users);
    }

    // Lister les utilisateurs par cohorte
    public function listByCohorte($cohorte_id)
    {
        $cohorte = Cohorte::find($cohorte_id);
        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        $users = Users::where('cohorte_id', $cohorte_id)->get();
        return response()->json($users);
    }

// Importer des utilisateurs à partir d'un CSV pour un département
public function importCSVForDepartement(Request $request, $departement_id)
{
    return $this->importCSV($request, $departement_id, null);
}

// Importer des utilisateurs à partir d'un CSV pour une cohorte
public function importCSVForCohorte(Request $request, $cohorte_id)
{
    return $this->importCSV($request, null, $cohorte_id);
}

// Méthode principale pour importer des utilisateurs à partir d'un CSV
private function importCSV(Request $request, $departement_id = null, $cohorte_id = null)
{
    // Validation du fichier CSV
    $validator = Validator::make($request->all(), [
        'csv_file' => 'required|file|mimes:csv,txt',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    // Lire le fichier CSV
    $file = $request->file('csv_file');
    $csv = Reader::createFromPath($file->getPathname(), 'r');
    $csv->setHeaderOffset(0);

    $errors = [];
    $importedUsers = [];
    $lineNumber = 1;

    foreach ($csv as $record) {
        $lineNumber++;

        // Validation des données du CSV
        $validator = Validator::make($record, [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'required|string|max:20',
            'adresse' => 'nullable|string',
            'photo' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            $errors[] = [
                'line' => $lineNumber,
                'errors' => $validator->errors(),
                'data' => $record,
            ];
            continue;
        }

        // Créer une requête pour chaque enregistrement
        $userRequest = new Request([
            'nom' => $record['nom'],
            'prenom' => $record['prenom'],
            'email' => $record['email'],
            'telephone' => $record['telephone'],
            'adresse' => $record['adresse'],
            'photo' => $record['photo'],
            'status' => null, // Ajout de status avec une valeur par défaut null
            'cardID' => null, // Ajout de cardID avec une valeur par défaut null
        ]);

        // Créer l'utilisateur en fonction du département ou de la cohorte
        if ($departement_id) {
            $response = $this->createFromDepartement($userRequest, $departement_id);
        } elseif ($cohorte_id) {
            $response = $this->createFromCohorte($userRequest, $cohorte_id);
        } else {
            $errors[] = [
                'line' => $lineNumber,
                'errors' => ['message' => 'Aucun département ou cohorte spécifié'],
                'data' => $record,
            ];
            continue;
        }

        if ($response->getStatusCode() === 201) {
            $importedUsers[] = $response->getData();
        } else {
            $errors[] = [
                'line' => $lineNumber,
                'errors' => $response->getData(),
                'data' => $record,
            ];
        }
    }

    return response()->json([
        'imported_users' => $importedUsers,
        'errors' => $errors,
    ], 200);
}

  
public function countByRole($role)
{
    $count = Users::where('role', $role)->count();
    return response()->json($count);
}

public function Count()
{
    $count = [
        'employes' => Users::where('role', 'employe')->count(),
        'apprenants' => Users::where('role', 'apprenant')->count(),
        'admins' => Users::where('role', 'admin')->count(),
        'vigiles' => Users::where('role', 'vigile')->count()
    ];
    
    return response()->json($count);
}

}