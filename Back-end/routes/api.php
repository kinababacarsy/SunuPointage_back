<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\UserController;

// Routes pour les départements
Route::get('departements', [DepartementController::class, 'list']);
Route::post('ajout/departements', [DepartementController::class, 'create']);
Route::get('voir/departements/{id}', [DepartementController::class, 'view']);
Route::put('maj/departements/{id}', [DepartementController::class, 'update']);
Route::delete('sup/departements/{id}', [DepartementController::class, 'delete']);
Route::get('/departements/count', [DepartementController::class, 'count']);

// Routes pour les cohortes
Route::get('cohortes', [CohorteController::class, 'list']);
Route::post('ajout/cohortes', [CohorteController::class, 'create']);
Route::get('voir/cohortes/{id}', [CohorteController::class, 'view']);
Route::put('maj/cohortes/{id}', [CohorteController::class, 'update']);
Route::delete('sup/cohortes/{id}', [CohorteController::class, 'delete']);
Route::get('/cohortes/count', [CohorteController::class, 'count']);

// Routes pour les utilisateurs
Route::get('users', [UserController::class, 'list']);
Route::post('ajout/users', [UserController::class, 'create']);
Route::get('voir/users/{id}', [UserController::class, 'view']);
Route::put('maj/users/{id}', [UserController::class, 'update']);
Route::delete('sup/users/{id}', [UserController::class, 'delete']);

// Routes pour ajouter un utilisateur à partir d'un département
Route::post('departements/{departement_id}/ajout/users', [UserController::class, 'createFromDepartement']);
// Route pour importer des utilisateurs à partir d'un département
Route::post('/departements/{departement_id}/import-users', [UserController::class, 'importCSVForDepartement']);


// Routes pour afficher les utilisateurs à partir d'un département
Route::get('/users/departement/{departement_id}', [UserController::class, 'listByDepartement']);

// Récupérer le nombre d'employés dans un département
Route::get('/departements/{departement_id}/employee-count', [DepartementController::class, 'getEmployeeCount']);

// Récupérer le nombre d'apprenants dans une cohorte
Route::get('/cohortes/{cohorte_id}/apprenant-count', [CohorteController::class, 'getApprenantCount']);

// Routes pour ajouter un utilisateur à partir d'une cohorte
Route::post('cohortes/{cohorte_id}/ajout/users', [UserController::class, 'createFromCohorte']);

// Route pour importer des utilisateurs à partir d'une cohorte
Route::post('/cohortes/{cohorte_id}/import-users', [UserController::class, 'importCSVForCohorte']);

// Routes pour afficher les utilisateurs à partir d'une cohorte
Route::get('/users/cohorte/{cohorte_id}', [UserController::class, 'listByCohorte']);


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
