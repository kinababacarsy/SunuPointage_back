<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControleAccesController;
use App\Http\Controllers\ForgotPasswordController;
use Illuminate\Http\Request;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\CohorteController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



// Route pour récupérer les informations du vigile connecté
Route::get('user/vigile-info', [UserController::class, 'getVigileInfo']);


// Route pour la connexion
Route::post('login', [AuthController::class, 'login']); // Route pour la connexion

// Route pour la déconnexion (révocation du token)
Route::post('logout', [AuthController::class, 'logout']); // Route pour la déconnexion

Route::post('/controle-acces', [ControleAccesController::class, 'store']);
Route::get('/controle-acces', [ControleAccesController::class, 'index']);
Route::get('/controle-acces/{id}', [ControleAccesController::class, 'show']);
Route::get('/controle-acces/user/{userId}', [ControleAccesController::class, 'getPointagesByUserId']);
Route::get('/controle-acces/pointages/{cardID}', [ControleAccesController::class, 'getPointagesByCardId']); 
Route::post('/controle-acces/absence', [ControleAccesController::class, 'storeAbsence']);
Route::get('/controle-acces/user/{userId}/absences', [ControleAccesController::class, 'getAbsencesByUserId']);
// Récupérer les pointages par cardID

// Routes pour la réinitialisation de mot de passe
Route::post('password/email', [ForgotPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');

// Test de connexion à la base de données MongoDB

Route::get('/test-db', function () {
    try {
        $result = DB::connection('mongodb')->collection('test_collection')->get();
        return response()->json($result);
    } catch (\Exception $e) {
        Log::error("MongoDB connection error: " . $e->getMessage());
        return response()->json(['error' => 'MongoDB connection failed.'], 500);
    }
});






// Routes pour les départements
Route::get('departements', [DepartementController::class, 'list']);
Route::post('ajout/departements', [DepartementController::class, 'create']);
Route::get('voir/departements/{id}', [DepartementController::class, 'view']);
Route::put('maj/departements/{id}', [DepartementController::class, 'update']);
Route::delete('sup/departements/{id}', [DepartementController::class, 'delete']);
Route::get('/departements/count', action: [DepartementController::class, 'count']);

// Routes pour les cohortes
Route::get('cohortes', [CohorteController::class, 'list']);
Route::post('ajout/cohortes', [CohorteController::class, 'create']);
Route::get('voir/cohortes/{id}', [CohorteController::class, 'view']);
Route::put('maj/cohortes/{id}', [CohorteController::class, 'update']);
Route::delete('sup/cohortes/{id}', [CohorteController::class, 'delete']);
Route::get('/cohortes/count', [CohorteController::class, 'count']);

// Routes pour les utilisateurs
// Routes pour les utilisateurs
Route::get('users', [UserController::class, 'index']); // Lister tous les utilisateurs
Route::post('ajout/users', [UserController::class, 'store']); // Créer un utilisateur
Route::get('voir/users/{id}', [UserController::class, 'show']); // Voir un utilisateur spécifique
Route::put('maj/users/{id}', [UserController::class, 'update']); // Mettre à jour un utilisateur
Route::delete('sup/users/{id}', [UserController::class, 'destroy']); // Supprimer un utilisateur
Route::get('/users/count', [UserController::class, 'count']); //compte le nombre d'utilisateurs
Route::get('/users/count/{role}', [UserController::class, 'countByRole']); //compte le nombre d'utilisateurs par role

Route::get('users/presences', [UserController::class, 'getUserPresences']); //liste de presences
Route::get('users/historique', [UserController::class, 'getUserHistorique']); //historique des pointages
// Dans routes/api.php
Route::get('/users/presences/date/{date}', [UserController::class, 'getPresencesByDate']);





// Routes pour la suppression multiple
Route::delete('sup/multiple/users', [UserController::class, 'deleteMultiple']);

// Routes pour bloquer et débloquer un utilisateur
Route::patch('users/bloquer/{id}', [UserController::class, 'bloquer']);
Route::patch('users/debloquer/{id}', [UserController::class, 'debloquer']);

// Routes pour ajouter un utilisateur à partir d'un département
Route::post('departements/{departement_id}/ajout/users', [UserController::class, 'createFromDepartement']);
// Route pour importer des utilisateurs à partir d'un département
Route::post('/departements/{departement_id}/import-users', [UserController::class, 'importCSVForDepartement']);
// Route pour importer des utilisateurs à partir d'un département
Route::post('/cohortes/{cohorte_id}/import-users', [UserController::class, 'importCSVForCohorte']);


// Routes pour afficher les utilisateurs à partir d'un département
Route::get('/users/departement/{departement_id}', [UserController::class, 'listByDepartement']);
// Routes pour afficher les utilisateurs à partir d'une cohorte
Route::get('/users/cohorte/{cohorte_id}', [UserController::class, 'listByCohorte']); // Lister les utilisateurs d'une cohorte

// Récupérer le nombre d'employés dans un département
Route::get('/departements/{departement_id}/employee-count', [DepartementController::class, 'getEmployeeCount']);

// Récupérer le nombre d'apprenants dans une cohorte
Route::get('/cohortes/{cohorte_id}/apprenant-count', [CohorteController::class, 'getApprenantCount']);

// Routes pour ajouter un utilisateur à partir d'une cohorte
Route::post('cohortes/{cohorte_id}/ajout/users', [UserController::class, 'createFromCohorte']);

// Route pour obtenir l'utilisateur authentifié
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
