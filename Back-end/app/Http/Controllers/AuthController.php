<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth; // Importation du package JWTAuth\
use App\Models\User;
use Illuminate\Support\Facades\Hash;



class AuthController extends Controller
{
// Connexion de l'utilisateur
public function login(Request $request)
{
    $credentials = $request->only('email', 'mot_de_passe');

    // Validation des données de connexion
    $validator = Validator::make($credentials, [
        'email' => 'required|email',
        'mot_de_passe' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['error' => 'Validation échouée', 'details' => $validator->errors()], 400);
    }

    // Recherche de l'utilisateur dans la base de données
    $user = User::where('email', $credentials['email'])->first();

    // Vérification de l'utilisateur et du mot de passe
    if (!$user || !Hash::check($credentials['mot_de_passe'], $user->mot_de_passe)) {
        return response()->json(['error' => 'Identifiants invalides'], 401);
    }

    // Authentifier l'utilisateur avec JWT
    $token = JWTAuth::fromUser($user);

    // Retourner le token JWT
    return response()->json(['token' => $token], 200);
}


public function logout()
{
    try {
        // Vérifier la présence du token
        $token = JWTAuth::getToken();
        if (!$token) {
            return response()->json(['error' => 'Token non fourni'], 400);
        }

        // Révoquer le token
        JWTAuth::invalidate($token);

        // Retourner une réponse de succès
        return response()->json(['message' => 'Déconnexion réussie'], 200);
    } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
        // Gestion d'erreur si le token est invalide
        return response()->json(['error' => 'Token invalide'], 401);
    } catch (\Exception $e) {
        // Gestion d'erreur générale
        return response()->json(['error' => 'Erreur lors de la déconnexion'], 500);
    }
}


}

