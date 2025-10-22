<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentSeat extends Model
{
    protected $table = 'current_seats';
    protected $primaryKey = 'current_id';
    protected $fillable = ['seat_id', 'employee_id', 'assigned_date',];
}
