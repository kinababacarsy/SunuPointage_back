<?php
namespace App\Models;

use Illuminate\MongoBD\Laravel\Eloquent\Model;
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
        'nbre_apprenant',
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
        return $this->hasMany(Users::class, 'cohorte_id'); // Relation avec 'cohorte_id'
    }
}
