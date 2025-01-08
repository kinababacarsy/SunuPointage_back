<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PasswordReset extends Model
{
    protected $connection = 'mongodb'; // Connexion MongoDB
    protected $collection = 'password_resets';  // Collection associée

    protected $fillable = [
        'email',
        'token',
        'created_at',
    ];

    protected $dates = ['created_at'];
}
