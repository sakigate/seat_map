<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $table = 'seats';
    protected $fillable = ['seat_name', 'office_id'];
}
