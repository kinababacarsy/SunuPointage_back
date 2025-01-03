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
Route::get('/users/count', [UserController::class, 'count']);
Route::get('/users/count/{role}', [UserController::class, 'countByRole']);



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

