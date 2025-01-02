<?php
namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cohorte extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'cohortes';

    protected $fillable = [
        'nom_cohorte',
        'description',
        'deleted_at'
    ];

    protected $dates = ['deleted_at'];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relation avec les utilisateurs
    public function users()
    {
        return $this->hasMany(Users::class, 'cohorte_id');
    }

    // Méthode pour obtenir le nombre d'apprenants
    public function getApprenantCountAttribute()
    {
        return $this->users()->where('role', 'apprenant')->count();
    }
}