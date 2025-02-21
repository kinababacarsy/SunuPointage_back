<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Notifications\ResetPasswordNotification;

class ForgotPasswordController extends Controller
{
    // Envoi du lien de réinitialisation de mot de passe
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Récupérer l'utilisateur correspondant à l'e-mail
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        // Générer le token de réinitialisation
        $token = Password::createToken($user);

        // Enregistrer le token dans la base de données
        $resetRecord = new PasswordReset();
        $resetRecord->email = $request->email;
        $resetRecord->token = $token;
        $resetRecord->save();

        // Envoyer la notification avec le token
        $user->sendPasswordResetNotification($token);

        return response()->json(['message' => 'Lien de réinitialisation envoyé.'], 200);
    }

    // Réinitialisation du mot de passe
    public function resetPassword(Request $request)
    {
        Log::info('Request Data:', ['data' => $request->all()]);  // Correction avec un tableau pour le contexte

        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'mot_de_passe' => 'required|confirmed|min:8',
        ]);

        // Vérifier si le token existe dans la collection password_resets
        $resetRecord = PasswordReset::where('email', $request->email)
                                    ->where('token', $request->token)
                                    ->first();

        Log::info('Reset Record Found:', ['record' => $resetRecord ? $resetRecord->toArray() : 'None']);  // Utilisation d'un tableau pour le contexte

        if (!$resetRecord) {
            return response()->json(['message' => 'Token de réinitialisation invalide ou expiré.'], 400);
        }

        // Récupérer l'utilisateur correspondant à l'email
        $user = User::where('email', $request->email)->first();

        Log::info('User Found:', ['user' => $user ? $user->toArray() : 'None']);  // Utilisation d'un tableau pour le contexte

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        // Hacher le nouveau mot de passe
        $user->password = bcrypt($request->password);
        $user->save();  // Sauvegarder l'utilisateur mis à jour

        // Supprimer le token de la collection password_resets
        $resetRecord->delete();

        Log::info('Password reset successfully for email:', ['email' => $request->email]);  // Correct avec un tableau pour le contexte

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.'], 200);
    }
}
