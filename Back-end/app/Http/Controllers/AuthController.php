<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth; // Importation du package JWTAuth
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
  // Connexion de l'utilisateur
  public function login(Request $request)
  {
      $credentials = $request->only('email', 'mot_de_passe', 'cardID'); // Récupérer email, mot de passe et cardID

      // Validation des données de connexion
      $validator = Validator::make($credentials, [
          'email' => 'nullable|email', // email est optionnel
          'mot_de_passe' => 'nullable|string', // mot_de_passe est optionnel mais requis pour email
          'cardID' => 'nullable|string', // cardID est optionnel mais requis pour admin uniquement
      ]);

      if ($validator->fails()) {
          return response()->json(['error' => 'Validation échouée', 'details' => $validator->errors()], 400);
      }

      // Connexion via cardID (uniquement pour les admins)
      if (!empty($credentials['cardID'])) {
          // Chercher l'utilisateur par cardID et vérifier que c'est un admin
          $user = User::where('cardID', $credentials['cardID'])
                      ->where('role', 'admin') // Seule les admins peuvent se connecter avec cardID
                      ->first();

          if (!$user) {
              return response()->json(['error' => 'Utilisateur non trouvé ou accès non autorisé'], 401);
          }

          // Si l'admin est trouvé, générer un token sans avoir besoin du mot de passe
          $token = JWTAuth::fromUser($user);

          // Retourner le token et le rôle de l'utilisateur
    // Retourner le token JWT, le rôle et les informations de l'utilisateur
    return response()->json([
        'token' => $token,
        'role' => $user->role,
        'user' => [
            'id' => $user->id,
            'nom' => $user->nom,
            'email' => $user->email,
            
            'photo' => $user->photo,
        ],
    ], 200);
      }

      // Connexion via email et mot de passe (tous les utilisateurs)
      if (!empty($credentials['email']) && !empty($credentials['mot_de_passe'])) {
          // Chercher l'utilisateur par email
          $user = User::where('email', $credentials['email'])->first();

          // Vérification de l'utilisateur et du mot de passe
          if (!$user || !Hash::check($credentials['mot_de_passe'], $user->mot_de_passe)) {
              return response()->json(['error' => 'Identifiants invalides'], 401);
          }

          // Authentifier l'utilisateur avec JWT
          $token = JWTAuth::fromUser($user);

    // Retourner le token JWT, le rôle et les informations de l'utilisateur
    return response()->json([
        'token' => $token,
        'role' => $user->role,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            
            'photo' => $user->photo,
        ],
    ], 200);
          
      }

      // Si aucune des conditions n'est remplie
      return response()->json(['error' => 'Identifiants manquants ou invalides'], 400);
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
