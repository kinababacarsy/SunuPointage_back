<?php

namespace App\Models;

use Illuminate\MongoBD\Laravel\Eloquent\Model;

class Action extends Model
{
    //
    protected $connection = 'mongodb';
    protected $collection = 'actions';

    protected $fillable =
     [
        'userId',
        'targetUserId',
        'action',
        'date',
        'heure'
    ];
}
