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
        'nbre_employe',
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
}
