<?php
namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cohorte extends Model

{

    // Utilisation de SoftDeletes pour la gestion des suppressions logiques
    use SoftDeletes;

        // Spécification de la connexion MongoDB
    protected $connection = 'mongodb';

        // Nom de la collection MongoDB
    protected $collection = 'cohortes';

        // Attributs remplissables
    protected $fillable = [
        'nom_cohorte',
        'description',
        'deleted_at'
    ];

    // Définition des dates à traiter comme des instances Carbon
    protected $dates = ['deleted_at'];

        // Clé primaire non incrémentée
    public $incrementing = false;
    protected $keyType = 'string';

    // Relation avec les utilisateurs (un Cohorte a plusieurs Users)
    public function users()
    {
        return $this->hasMany(User::class, 'cohorte_id');
    }

    // Méthode pour obtenir le nombre d'apprenants
    public function getApprenantCountAttribute()
    {
        return $this->users()->where('role', 'apprenant')->count();
    }
}
