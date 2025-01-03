<?php
namespace App\Models;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



