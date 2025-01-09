<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControleAccesController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;

Route::prefix('user')->group(function () {
    Route::post('/', [UserController::class, 'store']); // Créer un utilisateur
    Route::get('/', [UserController::class, 'index']); // Lister tous les utilisateurs
    Route::get('/{id}', [UserController::class, 'show']); // Récupérer un utilisateur
    Route::put('/{id}', [UserController::class, 'update']); // Mettre à jour un utilisateur
    Route::delete('/{id}', [UserController::class, 'destroy']); // Supprimer un utilisateur
});


// Route pour récupérer les informations du vigile connecté
Route::get('user/vigile-info', [UserController::class, 'getVigileInfo']);


// Route pour la connexion
Route::post('login', [AuthController::class, 'login']); // Route pour la connexion

// Route pour la déconnexion (révocation du token)
Route::post('logout', [AuthController::class, 'logout']); // Route pour la déconnexion

// Routes pour la gestion des contrôles d'accès (pointages Check-In, Check-Out)
Route::post('/controle-acces', [ControleAccesController::class, 'store']); // Enregistrer un pointage (Check-In / Check-Out)
Route::get('/controle-acces', [ControleAccesController::class, 'index']); // Lister tous les pointages
Route::get('/controle-acces/{id}', [ControleAccesController::class, 'show']); // Récupérer un pointage spécifique par ID
Route::get('/controle-acces/pointages/{cardID}', [ControleAccesController::class, 'getPointagesByCardId']); // Récupérer les pointages par cardID

// Routes pour la réinitialisation de mot de passe
Route::post('password/email', [ForgotPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');

// Test de connexion à la base de données MongoDB
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

Route::get('/test-db', function () {
    try {
        $result = DB::connection('mongodb')->collection('test_collection')->get();
        return response()->json($result);
    } catch (\Exception $e) {
        Log::error("MongoDB connection error: " . $e->getMessage());
        return response()->json(['error' => 'MongoDB connection failed.'], 500);
    }
});
