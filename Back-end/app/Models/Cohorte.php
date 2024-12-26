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
        'nbre_employe',
        'description',
        'date_creation',
        'date_modification',
        'deleted_at'
    ];

    protected $dates = ['deleted_at'];
}
