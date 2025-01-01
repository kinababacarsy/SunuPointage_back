<?php 
namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class User extends Model implements AuthenticatableContract
{
   use Authenticatable;

   protected $connection = 'mongodb';
   protected $collection = 'users';

   protected $fillable = [
    'matricule',
    'nom',
    'prenom',
    'email',
    'mot_de_passe',
    'telephone',
    'adresse',
    'photo',
    'role',
    'departement_id',
    'cohorte_id',
    'cardID',
    'status'
   ];

   protected $hidden = [
       'remember_token',
   ];
}