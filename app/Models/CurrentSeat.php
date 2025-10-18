<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentSeat extends Model
{
    protected $table = 'current_seats';
    protected $fillable = ['seat_name', 'employee_name', 'assigned_date',];
}
