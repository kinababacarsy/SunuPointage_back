<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Departement;
use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Importer la façade Log
use League\Csv\Reader;

class UserController extends Controller
{
    // Lister tous les utilisateurs
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
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
            'departement_id' => 'nullable|string|exists:departements,_id',
            'cohorte_id' => 'nullable|string|exists:cohortes,_id',
            'cardID' => 'nullable|string',
            'status' => 'sometimes|string|in:Actif,Bloque,Supprime',
        ];
    
        // Ajouter la règle pour le mot de passe uniquement pour admin et vigile
        if (in_array($request->role, ['admin', 'vigile'])) {
            $rules['mot_de_passe'] = 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*\d).+$/';
        } else {
            $rules['mot_de_passe'] = 'nullable|string';
        }
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            Log::error('Validation failed', ['errors' => $validator->errors()]);
            return response()->json($validator->errors(), 400);
        }
    
        return $request->all();
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
        // Valider les données
        $validatedData = $this->validateUserData($request);
    
        // Générer le matricule
        $validatedData['matricule'] = $this->generateMatricule($validatedData['role']);
    
        // Gestion du mot de passe
        if (in_array($validatedData['role'], ['admin', 'vigile'])) {
            if (empty($validatedData['mot_de_passe'])) {
                return response()->json(['message' => 'Un mot de passe est requis pour les rôles admin et vigile'], 400);
            }
            $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);
        } else {
            $validatedData['mot_de_passe'] = null;
        }
    
        // Photo par défaut si non fournie
        if (empty($validatedData['photo'])) {
            $validatedData['photo'] = 'inconnu.png';
        }
    
        // Statut par défaut si non fourni
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'Actif';
        }
        if (!isset($validatedData['cardID'])) {
            $validatedData['cardID'] = null;
        }
    
        // Créer l'utilisateur avec la méthode create
        try {
            $user = User::create($validatedData);
            Log::info('User created', ['user' => $user]);
            return response()->json([
                'message' => 'Utilisateur ajouté avec succès !',
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'utilisateur: ' . $e->getMessage());
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

        // Valider uniquement les champs fournis dans la requête
        $validatedData = $request->validate([
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'telephone' => 'sometimes|string|digits:9|regex:/^[0-9]+$/',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'adresse' => 'sometimes|string',
            'role' => 'sometimes|string|in:admin,vigile,employe,apprenant',
            'photo' => 'sometimes|string',
            'departement_id' => 'sometimes|string|exists:departements,_id',
            'cohorte_id' => 'sometimes|string|exists:cohortes,_id',
            'cardID' => 'sometimes|string',
            'status' => 'sometimes|string|in:Actif,Bloque,Supprime',
            'mot_de_passe' => 'sometimes|string|min:8|regex:/^(?=.*[A-Z])(?=.*\d).+$/',
        ]);

        // Hash le mot de passe si modifié
        if (isset($validatedData['mot_de_passe'])) {
            $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);
        }

        // Mettre à jour uniquement les champs fournis
        $user->fill($validatedData)->save();

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
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'line' => $lineNumber,
                    'errors' => $validator->errors(),
                    'data' => $record,
                ];
                continue;
            }
        // Photo par défaut si non fournie
        if (empty($validatedData['photo'])) {
            $validatedData['photo'] = 'inconnu.png';
        }
            
            // Créer une requête pour chaque enregistrement
            $userRequest = new Request([
                'nom' => $record['nom'],
                'prenom' => $record['prenom'],
                'email' => $record['email'],
                'telephone' => $record['telephone'],
                'adresse' => $record['adresse'],
                
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
    public function createFromDepartement(Request $request, $departement_id)
    {
        $departement = Departement::find($departement_id);

        if (!$departement) {
            return response()->json(['message' => 'Département non trouvé'], 404);
        }

        // Définir le rôle comme "employe" par défaut, sauf si spécifié autrement
        $role = $request->role ?? 'employe';
        $request->merge(['departement_id' => $departement_id, 'role' => $role]);
        return $this->store($request);
    }

    // Créer un utilisateur à partir d'une cohorte
    public function createFromCohorte(Request $request, $cohorte_id)
    {
        $cohorte = Cohorte::find($cohorte_id);

        if (!$cohorte) {
            return response()->json(['message' => 'Cohorte non trouvée'], 404);
        }

        // Définir le rôle comme "apprenant" pour les utilisateurs créés via une cohorte
        $request->merge(['cohorte_id' => $cohorte_id, 'role' => 'apprenant']);
        return $this->store($request);
    }

    // Générer un matricule
    private function generateMatricule($role)
    {
        $prefixes = [
            'admin' => 'AD',
            'vigile' => 'VI',
            'employe' => 'EMP',
            'apprenant' => 'APP',
        ];

        $prefix = $prefixes[$role];
        $lastUser = User::where('role', $role)->orderBy('id', 'desc')->first();
        $lastNumber = $lastUser ? (int)preg_replace('/[^0-9]/', '', $lastUser->matricule) : 0;
        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . '-' . $newNumber;
    }

    // Ajouter un cardID à un utilisateur
    public function addCardId(Request $request, $id)
    {
        // Valider les données de la requête
        $validatedData = $request->validate([
            'cardID' => 'required|string',
        ]);

        // Récupérer l'utilisateur par son ID
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        // Mettre à jour le cardID de l'utilisateur
        $user->cardID = $validatedData['cardID'];
        $user->save();

        return response()->json([
            'message' => 'CardID ajouté avec succès !',
            'user' => $user,
        ], 200);
    }

    // Compte les utilisateurs par leur rôle
    public function countByRole($role)
    {
        $count = User::where('role', $role)->count();
        return response()->json($count);
    }

    public function count()
    {
        $count = [
            'employes' => User::where('role', 'employe')->count(),
            'apprenants' => User::where('role', 'apprenant')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'vigiles' => User::where('role', 'vigile')->count()
        ];

        return response()->json($count);
    }

    // Liste des présences
    public function getUserPresences(Request $request)
    {
        $query = User::select('matricule', 'prenom', 'nom');

        if ($request->has('date')) {
            $date = $request->input('date');
            $query->whereDate('created_at', $date);
        }

        $users = $query->get();

        return response()->json($users);
    }

    // Historique des présences
    public function getUserHistorique()
    {
        // Supposons que vous avez un champ 'status' dans la collection 'users' qui stocke les historiques des présences
        $historique = User::select('status')
                         ->groupBy('status')
                         ->selectRaw('count(*) as count')
                         ->get()
                         ->map(function ($item) {
                             return [
                                 'status' => $item->status,
                                 'count' => $item->count
                             ];
                         });

        return response()->json($historique);
    }
}
