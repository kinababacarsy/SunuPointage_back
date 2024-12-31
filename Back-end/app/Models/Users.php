<?php 
namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Users extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'photo',
        'role',
        'departement_id',
        'cohorte_id',
        'cardID',
        'statut'
    ];

    protected $hidden = [
        'remember_token',
    ];

    // Relation avec le département
    public function departement()
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    // Relation avec la cohorte
    public function cohorte()
    {
        return $this->belongsTo(Cohorte::class, 'cohorte_id');
    }
}
