<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    // Lister tous les utilisateurs
    public function index()
    {
        $users = User::all(); // Utilisation d'Eloquent pour récupérer tous les utilisateurs
        return response()->json($users, 200);
    }

    // Récupérer un utilisateur par ID
    public function show($id)
    {
        $user = User::find($id); // Utilisation d'Eloquent pour rechercher un utilisateur par son ID

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
            'matricule' => 'required|string|unique:users,matricule', 
            'nom' => 'required|string', 
            'prenom' => 'required|string', 
            'email' => 'required|email|unique:users,email', 
            'mot_de_passe' => 'required|string|min:8', // Ajout de validation pour mot de passe            
            'adresse' => 'required|string', 
            'role' => 'required|string|in:admin,vigile,employe,apprenant',
            'photo' => 'nullable|string', // Permet de soumettre une photo, sinon on prendra la valeur par défaut            
            'departement_id' => 'nullable|string', 
            'cohorte_id' => 'nullable|string',
            'cardID' => 'required|string', 
            'status' => 'required|boolean', 
        ]);

         // Vérification si une photo a été envoyée, sinon définir la photo par défaut
        if (empty($validatedData['photo'])) {
            $validatedData['photo'] = 'images/inconnu.png'; // Spécifier le chemin de l'image par défaut
        }

        // Hash du mot de passe
        $validatedData['mot_de_passe'] = Hash::make($validatedData['mot_de_passe']);



        try {
            // Création de l'utilisateur
            $user = User::create($validatedData); // Utilisation d'Eloquent pour créer un utilisateur
    
            // Retourner un message de succès avec les informations de l'utilisateur
            return response()->json([
                'message' => 'Utilisateur ajouté avec succès !',
                'user' => $user
            ], 201)->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Échec de l\'ajout de l\'utilisateur.',
                'error' => $e->getMessage()
            ], 201)->header('Content-Type', 'application/json; charset=utf-8');// Code d'erreur 500 si une exception survient
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
            'photo' => 'sometimes|string', 
            'role' => 'sometimes|string', 
            'departement_id' => 'sometimes|string', 
            'cohorte_id' => 'sometimes|string', 
            'status' => 'sometimes|boolean', 
        ]);

        // Mise à jour de l'utilisateur avec les données validées
        $user->update($validatedData); // Utilisation d'Eloquent pour mettre à jour l'utilisateur
        return response()->json($user, 200); // Retourner la réponse JSON avec l'utilisateur mis à jour
    }

    // Supprimer un utilisateur
    public function destroy($id)
    {
        $user = User::find($id); // Trouver l'utilisateur par son ID

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404); // Si l'utilisateur n'existe pas
        }

        $user->delete(); // Supprimer l'utilisateur avec Eloquent
        return response()->json(['message' => 'Utilisateur supprimé'], 200); // Retourner un message de confirmation
    }
}
