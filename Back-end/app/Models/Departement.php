<?php

namespace App\Models;

use Illuminate\MongoBD\Laravel\Eloquent\Model;

class Departement extends Model
{
    //

    protected $connection = 'mongodb';
    protected $collection = 'departements';

    protected $fillable = [
        'nom_departement',
        'nbre_employe',
        'description',
        'date_creation',
        'date_modification'
    ];
}
