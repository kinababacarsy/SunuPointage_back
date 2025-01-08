<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ControleAcces extends Model
{
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

    // Définir la valeur par défaut pour le statut
    protected $attributes = [
        'statut' => 'En attente', // Valeur par défaut
    ];
}
