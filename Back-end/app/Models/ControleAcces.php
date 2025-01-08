<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ControleAcces extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'controleacces'; // Mettez à jour le nom de la collection ici

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

      // Relation : Un enregistrement de pointage appartient à un utilisateur
      public function user()
      {
          return $this->belongsTo(User::class, 'userId', '_id');
      }
}
