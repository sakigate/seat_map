<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seat extends Model
{
    protected $table = 'seats';
    protected $primaryKey = 'seat_id';
    protected $fillable = ['seat_name', 'office_id', 'x_position', 'y_position', 'width', 'height'];

    // タイムスタンプを無効にする
    public $timestamps = false;
}
