<?php

namespace App\Models;

use Illuminate\MongoBD\Laravel\Eloquent\Model;

class Cohorte extends Model
{
    //

    protected $connection = 'mongodb';
    protected $collection = 'cohortes';

    protected $fillable = [
        'nom_cohorte',
        'nbre_apprenant',
        'description',
        'date_creation'
    ];
}
