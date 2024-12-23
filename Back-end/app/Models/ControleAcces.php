<?php

namespace App\Models;

use Illuminate\MongoBD\Laravel\Eloquent\Model;

class ControleAcces extends Model
{
    //


    protected $connection = 'mongodb';
    protected $collection = 'controle_acces';

    protected $fillable = [
        'userId',
        'date',
        'heure',
        'type',
        'statut',
        'heureEntreePrevue',
        'heureDescentePrevue',
        'etat'
    ];
}
