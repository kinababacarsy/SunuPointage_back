<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartementController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ControleAccesController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\ResetPasswordController;

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
Route::get('users', [UserController::class, 'list']);
Route::post('ajout/users', [UserController::class, 'create']);
Route::get('voir/users/{id}', [UserController::class, 'view']);
Route::put('maj/users/{id}', [UserController::class, 'update']);
Route::delete('sup/users/{id}', [UserController::class, 'delete']);
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


// Routes pour afficher les utilisateurs à partir d'un département
Route::get('/users/departement/{departement_id}', [UserController::class, 'listByDepartement']);

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






/*<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;*/



Route::prefix('user')->group(function () {
    Route::post('/', [UserController::class, 'store']); // Créer un utilisateur
    Route::get('/', [UserController::class, 'index']); // Lister tous les utilisateurs
    Route::get('/{id}', [UserController::class, 'show']); // Récupérer un utilisateur
    Route::put('/{id}', [UserController::class, 'update']); // Mettre à jour un utilisateur
    Route::delete('/{id}', [UserController::class, 'destroy']); // Supprimer un utilisateur
});


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

Route::get('/users/{id}', [UserController::class, 'show']); //infos utilisateur
Route::put('/users/{id}/add-card', [UserController::class, 'addCardId']); // Ajouter un cardID à un utilisateur



// Routes pour ajouter un utilisateur à partir d'un département
Route::post('/departements/{departement_id}/ajout/users', [UserController::class, 'createFromDepartement']); // Créer un utilisateur à partir d'un département
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

Route::post('/controle-acces', [ControleAccesController::class, 'store']);
Route::get('/controle-acces', [ControleAccesController::class, 'index']);
Route::get('/controle-acces/{id}', [ControleAccesController::class, 'show']);
Route::get('/controle-acces/user/{userId}', [ControleAccesController::class, 'getPointagesByUserId']);
Route::get('/controle-acces/pointages/{cardID}', [ControleAccesController::class, 'getPointagesByCardId']); // Récupérer les pointages par cardID

// Routes pour la réinitialisation de mot de passe
Route::post('password/email', [ForgotPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('password/reset', [ForgotPasswordController::class, 'resetPassword'])->name('password.reset');



Route::get('/test-db', function () {
    try {
        $result = DB::connection('mongodb')->collection('test_collection')->get();
        return response()->json($result);
    } catch (\Exception $e) {
        Log::error("MongoDB connection error: " . $e->getMessage());
        return response()->json(['error' => 'MongoDB connection failed.'], 500);
    }
});
// Route pour importer des utilisateurs à partir d'une cohorte
Route::post('/cohortes/{cohorte_id}/import-users', [UserController::class, 'importCSVForCohorte']);

// Routes pour afficher les utilisateurs à partir d'une cohorte
Route::get('/users/cohorte/{cohorte_id}', [UserController::class, 'listByCohorte']);


