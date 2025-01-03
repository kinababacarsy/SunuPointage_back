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

// Routes pour les cohortes
Route::get('cohortes', [CohorteController::class, 'list']);
Route::post('ajout/cohortes', [CohorteController::class, 'create']);
Route::get('voir/cohortes/{id}', [CohorteController::class, 'view']);
Route::put('maj/cohortes/{id}', [CohorteController::class, 'update']);
Route::delete('sup/cohortes/{id}', [CohorteController::class, 'delete']);

// Routes pour les utilisateurs
Route::get('users', [UserController::class, 'list']);
Route::post('ajout/users', [UserController::class, 'create']);
Route::get('voir/users/{id}', [UserController::class, 'view']);
Route::put('maj/users/{id}', [UserController::class, 'update']);
Route::delete('sup/users/{id}', [UserController::class, 'delete']);

// Routes pour la suppression multiple
Route::delete('sup/multiple/users', [UserController::class, 'deleteMultiple']);

// Bloquer un utilisateur et désactiver sa carte RFID
Route::patch('users/bloquer/{id}', [UserController::class, 'bloquerUtilisateur']);

// Débloquer un utilisateur et activer sa carte RFID
Route::patch('users/debloquer/{id}', [UserController::class, 'debloquerUtilisateur']);


// Assigner une carte RFID à un utilisateur
Route::post('users/rfid/assigner/{id}', [UserController::class, 'assignerCarteRFID']);

// Vérifier l'état d'une carte RFID avant de l'utiliser
Route::get('users-/rfid/etat/{cardID}', [UserController::class, 'verifierEtatCarte']);

// Lecture d'une carte RFID
Route::post('users/rfid/lecture', [UserController::class, 'lireCarte']);

// Activer une carte RFID manuellement014
Route::patch('users/rfid/activer/{cardID}', [UserController::class, 'activerCarte']);

// Désactiver une carte RFID manuellement
Route::patch('users/rfid/desactiver/{cardID}', [UserController::class, 'desactiverCarte']);


// Routes pour ajouter un utilisateur à partir d'un département
Route::post('departements/{departement_id}/ajout/users', [UserController::class, 'createFromDepartement']);

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
