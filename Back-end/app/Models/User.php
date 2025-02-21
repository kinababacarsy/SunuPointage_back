<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject; // Importation de l'interface JWTSubject
use Illuminate\Notifications\Notifiable; // Importation du trait Notifiable
use App\Notifications\ResetPasswordNotification; // Importation de la notification personnalisée
use Illuminate\Contracts\Auth\CanResetPassword; // Importation de l'interface CanResetPassword

class User extends Model implements AuthenticatableContract, JWTSubject, CanResetPassword
{
    use Authenticatable, Notifiable; // Ajout du trait Notifiable

    protected $connection = 'mongodb'; // Connexion MongoDB
    protected $collection = 'users';  // Collection associée

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'email',
        'mot_de_passe', // Utiliser 'mot_de_passe' au lieu de 'password'
        'telephone',
        'adresse',
        'photo',
        'role',
        'departement_id',
        'cohorte_id',
        'cardID',
        'status',
        'resetPasswordToken',   // Token pour la réinitialisation
        'resetPasswordExpires', // Date d'expiration du token
    ];

    protected $hidden = [
        'remember_token',
    ];

    /**
     * Récupérer l'identifiant unique de l'utilisateur pour le JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Retourne la clé primaire (par défaut `_id` pour MongoDB)
    }

    /**
     * Récupérer les données supplémentaires que tu veux inclure dans le JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return []; // Ajouter des informations supplémentaires si nécessaire
    }

    /**
     * Envoyer la notification de réinitialisation du mot de passe.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the e-mail address where password reset links are sent.
     *
     * @return string
     */
    public function getEmailForPasswordReset()
    {
        return $this->email;
    }

    // Relation : Un utilisateur peut avoir plusieurs enregistrements de pointage
    public function controleAcces()
    {
        return $this->hasMany(ControleAcces::class, 'userId', '_id');
    }
}
