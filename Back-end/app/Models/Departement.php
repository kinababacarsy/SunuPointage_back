<?php
namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departement extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'departements';

    protected $fillable = [
        'nom_departement',
        'description',
        'deleted_at'
    ];

    protected $dates = ['deleted_at'];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relation avec les utilisateurs
    public function users()
    {
        return $this->hasMany(Users::class, 'departement_id');
    }

    // Méthode pour obtenir le nombre d'employés
    public function getEmployeeCountAttribute()
    {
        return $this->users()->where('role', 'employe')->count();
    }
}
