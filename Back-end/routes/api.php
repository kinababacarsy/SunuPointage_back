<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControleAccesController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Voici les routes de l'API pour gérer les départements, les cohortes, les utilisateurs,
| l'authentification et les contrôles d'accès.
|
*/

// Routes pour les départements
Route::get('departements', [DepartementController::class, 'list']); // Lister tous les départements
Route::post('ajout/departements', [DepartementController::class, 'create']); // Créer un département
Route::get('voir/departements/{id}', [DepartementController::class, 'view']); // Voir un département spécifique
Route::put('maj/departements/{id}', [DepartementController::class, 'update']); // Mettre à jour un département
Route::delete('sup/departements/{id}', [DepartementController::class, 'delete']); // Supprimer un département
Route::get('/departements/count', [DepartementController::class, 'count']); // Compter le nombre de départements
Route::get('/departements/{departement_id}/employee-count', [DepartementController::class, 'getEmployeeCount']); // Nombre d'employés dans un département

// Routes pour les cohortes
Route::get('cohortes', [CohorteController::class, 'list']); // Lister toutes les cohortes
Route::post('ajout/cohortes', [CohorteController::class, 'create']); // Créer une cohorte
Route::get('voir/cohortes/{id}', [CohorteController::class, 'view']); // Voir une cohorte spécifique
Route::put('maj/cohortes/{id}', [CohorteController::class, 'update']); // Mettre à jour une cohorte
Route::delete('sup/cohortes/{id}', [CohorteController::class, 'delete']); // Supprimer une cohorte
Route::get('/cohortes/count', [CohorteController::class, 'count']); // Compter le nombre de cohortes
Route::get('/cohortes/{cohorte_id}/apprenant-count', [CohorteController::class, 'getApprenantCount']); // Nombre d'apprenants dans une cohorte

// Routes pour les utilisateurs
Route::get('users', [UserController::class, 'index']); // Lister tous les utilisateurs
Route::post('ajout/users', [UserController::class, 'store']); // Créer un utilisateur
Route::get('voir/users/{id}', [UserController::class, 'show']); // Voir un utilisateur spécifique
Route::put('maj/users/{id}', [UserController::class, 'update']); // Mettre à jour un utilisateur
Route::delete('sup/users/{id}', [UserController::class, 'destroy']); // Supprimer un utilisateur

// Routes pour ajouter un utilisateur à partir d'un département
Route::post('departements/{departement_id}/ajout/users', [UserController::class, 'createFromDepartement']); // Créer un utilisateur à partir d'un département
// Route pour importer des utilisateurs à partir d'un département
Route::post('/departements/{departement_id}/import-users', [UserController::class, 'importCSVForDepartement']); // Importer des utilisateurs à partir d'un département

// Routes pour afficher les utilisateurs à partir d'un département
Route::get('/users/departement/{departement_id}', [UserController::class, 'listByDepartement']); // Lister les utilisateurs d'un département

// Routes pour ajouter un utilisateur à partir d'une cohorte
Route::post('cohortes/{cohorte_id}/ajout/users', [UserController::class, 'createFromCohorte']); // Créer un utilisateur à partir d'une cohorte
// Route pour importer des utilisateurs à partir d'une cohorte
Route::post('/cohortes/{cohorte_id}/import-users', [UserController::class, 'importCSVForCohorte']); // Importer des utilisateurs à partir d'une cohorte

// Routes pour afficher les utilisateurs à partir d'une cohorte
Route::get('/users/cohorte/{cohorte_id}', [UserController::class, 'listByCohorte']); // Lister les utilisateurs d'une cohorte

// Routes pour l'authentification
Route::post('login', [AuthController::class, 'login']); // Connexion
Route::post('logout', [AuthController::class, 'logout']); // Déconnexion

// Routes pour les contrôles d'accès (pointages)
Route::prefix('controle-acces')->group(function () {
    Route::post('/', [ControleAccesController::class, 'store']); // Enregistrer un pointage (Check-In / Check-Out)
    Route::get('/{userId}', [ControleAccesController::class, 'show']); // Récupérer les pointages d'un utilisateur
    Route::get('/all', [ControleAccesController::class, 'getAll']); // Récupérer tous les pointages
    Route::delete('/{id}', [ControleAccesController::class, 'destroy']); // Supprimer un pointage
});

// Route de test pour la connexion à MongoDB
Route::get('/test-db', function () {
    try {
        $result = DB::connection('mongodb')->collection('test_collection')->get();
        return response()->json($result);
    } catch (\Exception $e) {
        Log::error("MongoDB connection error: " . $e->getMessage());
        return response()->json(['error' => 'MongoDB connection failed.'], 500);
    }
});