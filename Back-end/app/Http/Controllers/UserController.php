<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class UserController extends Controller
{
    // Lister tous les utilisateurs
    public function index()
    {
        $users = User::all(); // Récupérer tous les utilisateurs
        return response()->json($users, 200);
    }

    // Récupérer un utilisateur par ID
    public function show($id)
    {
        $user = User::find($id); // Rechercher un utilisateur par son ID

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        return response()->json($user, 200);
    }

    // Créer un utilisateur
    public function store(Request $request)
    {
        // Validation des données reçues
        $validatedData = $request->validate([
            'nom' => 'required|string',
            'prenom' => 'required|string',
            'telephone' => 'required|string|digits:9|regex:/^[0-9]+$/',
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:8', // Validation pour mot de passe
            'adresse' => 'required|string',
            'role' => 'required|string|in:admin,vigile,employe,apprenant',
            'photo' => 'nullable|string', // Permet de soumettre une photo, sinon par défaut
            'departement_id' => 'nullable|string',
            'cohorte_id' => 'nullable|string',
            'cardID' => 'nullable|string',
            'status' => 'string|in:Actif,Bloque,Supprime', // Validation pour les statuts
        ]);

        // Générer le matricule en fonction du rôle
        $prefixes = [
            'admin' => 'AD',
            'employe' => 'EM',
            'apprenant' => 'APP',
            'vigile' => 'VI',
        ];

        $role = $validatedData['role'];
        $prefix = $prefixes[$role];

        // Récupérer le dernier matricule pour le rôle donné
        $lastUser = User::where('role', $role)
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastUser && preg_match('/\d+$/', $lastUser->matricule, $matches)) {
            $lastNumber = (int)$matches[0];
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        $validatedData['matricule'] = $prefix . '-' . $newNumber;

        // Vérification si une photo a été envoyée, sinon définir la photo par défaut
        if (empty($validatedData['photo'])) {
            $validatedData['photo'] = 'images/inconnu.png'; // Chemin de l'image par défaut
        }

        // Définir le statut par défaut à "Actif" si non fourni
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'Actif';
        }

        // Hash du mot de passe
        $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);

        try {
            // Création de l'utilisateur
            $user = User::create($validatedData); // Créer un utilisateur

            // Retourner un message de succès avec les informations de l'utilisateur
            return response()->json([
                'message' => 'Utilisateur ajouté avec succès !',
                'user' => $user,
            ], 201)->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de l\'ajout de l\'utilisateur.',
                'error' => $e->getMessage(),
            ], 500)->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    // Mettre à jour un utilisateur
    public function update(Request $request, $id)
    {
        $user = User::find($id); // Récupérer l'utilisateur par son ID

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404); // Si l'utilisateur n'existe pas
        }

        // Validation des données reçues
        $validatedData = $request->validate([
            'matricule' => 'sometimes|string|unique:users,matricule,' . $id,
            'nom' => 'sometimes|string',
            'prenom' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'telephone' => 'sometimes|string',
            'mot_de_passe' => 'sometimes|string',
            'adresse' => 'sometimes|string',
            'photo' => 'sometimes|string',
            'role' => 'sometimes|string',
            'departement_id' => 'sometimes|string',
            'cohorte_id' => 'sometimes|string',
            'cardID' => 'sometimes|string',
            'status' => 'sometimes|string|in:Actif,Bloque,Supprime', // Validation pour les statuts
        ]);

        // Hash le mot de passe si modifié
        if (isset($validatedData['mot_de_passe'])) {
            $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);
        }

        // Mise à jour de l'utilisateur avec les données validées
        $user->update($validatedData);
        return response()->json($user, 200);
    }

    // Supprimer un utilisateur
    public function destroy($id)
    {
        $user = User::find($id); // Trouver l'utilisateur par son ID

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }

        $user->delete(); // Supprimer l'utilisateur
        return response()->json(['message' => 'Utilisateur supprimé'], 200);
    }
    public function getVigileInfo(Request $request)
    {
        $user = $request->user();
        
        // Ajoutez un log pour vérifier si l'utilisateur est récupéré correctement
        Log::info('Utilisateur authentifié:', ['user' => $user]);
    
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
    
        if ($user->role !== 'vigile') {
            return response()->json(['error' => 'Accès refusé'], 403);
        }
    
        return response()->json([
            'nom' => $user->nom,
            'prenom' => $user->prenom,
            'email' => $user->email,
        ]);
    }
    

}
