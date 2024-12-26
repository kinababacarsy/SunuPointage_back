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
        'date_creation',
        'date_modification',
        'deleted_at'
    ];

    protected $dates = ['deleted_at'];
}
