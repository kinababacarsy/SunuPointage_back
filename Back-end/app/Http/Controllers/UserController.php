<?php

namespace App\Http\Controllers;

use App\Models\Departement;
use App\Models\Cohorte;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use League\Csv\Reader;

class UserController extends Controller
{
    // Lister tous les utilisateurs
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    // Récupérer un utilisateur par ID
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json($user, 200);
    }

    // Créer un utilisateur
    public function store(Request $request)
    {
        $validatedData = $this->validateUserData($request);

        // Générer le matricule
        $validatedData['matricule'] = $this->generateMatricule($validatedData['role']);

        // Gestion du mot de passe
        if (in_array($validatedData['role'], ['admin', 'vigile'])) {
            // Si le rôle est admin ou vigile, un mot de passe est requis
            if (empty($validatedData['mot_de_passe'])) {
                return response()->json(['message' => 'Un mot de passe est requis pour les rôles admin et vigile'], 400);
            }
            $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);
        } else {
            // Pour les autres rôles, le mot de passe n'est pas requis
            $validatedData['mot_de_passe'] = null;
        }

        // Photo par défaut si non fournie
        if (empty($validatedData['photo'])) {
            $validatedData['photo'] = 'images/inconnu.png';
        }

        // Statut par défaut si non fourni
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'Actif';
        }

        try {
            $user = User::create($validatedData);
            return response()->json([
                'message' => 'Utilisateur ajouté avec succès !',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de l\'ajout de l\'utilisateur.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Mettre à jour un utilisateur
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $validatedData = $this->validateUserData($request, $id);

        // Hash le mot de passe si modifié
        if (isset($validatedData['mot_de_passe'])) {
            $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);
        }

        $user->update($validatedData);
        return response()->json($user, 200);
    }

    // Supprimer un utilisateur
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $user->delete();
        return response()->json(null, 204);
    }

    // Lister les utilisateurs par département
    public function listByDepartement($departement_id)
    {
        $departement = Departement::find($departement_id);

        if (!$departement) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }

        $users = User::where('departement_id', $departement_id)->get();
        return response()->json($users);
    }

    // Lister les utilisateurs par cohorte
    public function listByCohorte($cohorte_id)
    {
        $cohorte = Cohorte::find($cohorte_id);

        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        $users = User::where('cohorte_id', $cohorte_id)->get();
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
                'role' => 'required|string|in:admin,vigile,employe,apprenant',
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
                'role' => $record['role'],
                'status' => 'Actif', // Statut par défaut
                'cardID' => null, // cardID par défaut
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

    // Créer un utilisateur à partir d'un département
    private function createFromDepartement(Request $request, $departement_id)
    {
        $departement = Departement::find($departement_id);

        if (!$departement) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }

        $request->merge(['departement_id' => $departement_id, 'role' => 'employe']);
        return $this->store($request);
    }

    // Créer un utilisateur à partir d'une cohorte
    private function createFromCohorte(Request $request, $cohorte_id)
    {
        $cohorte = Cohorte::find($cohorte_id);

        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        $request->merge(['cohorte_id' => $cohorte_id, 'role' => 'apprenant']);
        return $this->store($request);
    }

    // Générer un matricule
    private function generateMatricule($role)
    {
        $prefixes = [
            'admin' => 'AD',
            'employe' => 'EM',
            'apprenant' => 'APP',
            'vigile' => 'VI',
        ];

        $prefix = $prefixes[$role];
        $lastUser = User::where('role', $role)->orderBy('id', 'desc')->first();
        $lastNumber = $lastUser ? (int)preg_replace('/[^0-9]/', '', $lastUser->matricule) : 0;
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . '-' . $newNumber;
    }

    // Validation des données de l'utilisateur
    private function validateUserData(Request $request, $id = null)
    {
        $rules = [
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'telephone' => 'required|string|digits:9|regex:/^[0-9]+$/',
            'email' => 'required|email|unique:users,email,' . $id,
            'adresse' => 'required|string',
            'role' => 'required|string|in:admin,vigile,employe,apprenant',
            'photo' => 'nullable|string',
            'departement_id' => 'nullable|string',
            'cohorte_id' => 'nullable|string',
            'cardID' => 'nullable|string',
            'status' => 'sometimes|string|in:Actif,Bloque,Supprime',
        ];

        // Ajouter la règle pour le mot de passe uniquement pour admin et vigile
        if (in_array($request->role, ['admin', 'vigile'])) {
            $rules['mot_de_passe'] = 'required|string|min:8';
        } else {
            $rules['mot_de_passe'] = 'nullable|string';
        }

        return $request->validate($rules);
    }
}