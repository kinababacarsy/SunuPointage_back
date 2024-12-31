<?php
namespace App\Models;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Models\Users;

Route::get('/Users', function() {
   Users::create([
       'nom' ,
       'prenom'  ,
       'email' ,
       'mot de passe' ,
       'telephone' ,
    'adresse' ,
    'photo',
    'role' ,
    'departement_id',
    'cohorte_id',
    'cardID',
    'status'
   ]);
   
   return Users::all();
});


