<?php
namespace App\Models;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Models\User;

Route::get('/Users', function() {
   User::create([
       'nom' => ' Doe',
       'prenom' => 'John',
       'email' => 'john@example.com',
       'mot de passe' => ('password123'),
       'telephone' => '778965411',
    'adresse' =>'Fass',
    'photo',
    'role' =>'admin',
    'departement_id',
    'cohorte_id',
    'cardID',
    'status'
   ]);
   
   return User::all();
});


