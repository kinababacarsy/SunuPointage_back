<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControleAccesController;

Route::prefix('user')->group(function () {
    Route::post('/', [UserController::class, 'store']); // Créer un utilisateur
    Route::get('/', [UserController::class, 'index']); // Lister tous les utilisateurs
    Route::get('/{id}', [UserController::class, 'show']); // Récupérer un utilisateur
    Route::put('/{id}', [UserController::class, 'update']); // Mettre à jour un utilisateur
    Route::delete('/{id}', [UserController::class, 'destroy']); // Supprimer un utilisateur
});

// Route pour la connexion
Route::post('login', [AuthController::class, 'login']); // Route pour la connexion

// Route pour la déconnexion (révocation du token)
Route::post('logout', [AuthController::class, 'logout']); // Route pour la déconnexion


// Routes pour la gestion des contrôles d'accès (pointages Check-In, Check-Out)
Route::prefix('controle-acces')->group(function () {
    Route::post('/', [ControleAccesController::class, 'store']); // Enregistrer un pointage (Check-In / Check-Out)
    Route::get('/{userId}', [ControleAccesController::class, 'show']); // Récupérer les pointages d'un utilisateur
    Route::get('/all', [ControleAccesController::class, 'getAll']); // Récupérer tous les pointages
    Route::delete('/{id}', [ControleAccesController::class, 'destroy']); // Supprimer un pointage
});

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
