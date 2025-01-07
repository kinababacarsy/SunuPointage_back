<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'attendances';

    protected $fillable = [
        'user_id',
        'date',
        'first_check_in',
        'last_check_out',
        'status',
        'reason',
        'is_validated',
        'validated_by',
        'modified_by'
    ];

    protected $dates = [
        'date',
        'first_check_in',
        'last_check_out'
    ];

    protected $casts = [
        'is_validated' => 'boolean'
    ];

    // Relations
  /*  public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    } */
}
