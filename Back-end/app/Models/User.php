<?php 

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject; // Importation de l'interface JWTSubject

class User extends Model implements AuthenticatableContract, JWTSubject
{
    use Authenticatable;

    protected $connection = 'mongodb'; // Connexion MongoDB
    protected $collection = 'users';  // Collection associée

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

    /*
     
Récupérer l'identifiant unique de l'utilisateur pour le JWT.*
@return mixed*/
public function getJWTIdentifier(){
    return $this->getKey(); // Retourne la clé primaire (par défaut _id pour MongoDB)}
}
    /*
     
Récupérer les données supplémentaires que tu veux inclure dans le JWT.*
@return array*/
public function getJWTCustomClaims(){
    return []; // Ajouter des informations supplémentaires si nécessaire}
}
}