<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    protected $table = 'offices';
    protected $primaryKey = 'office_id';
    protected $fillable = ['office_name', 'layout_width', 'layout_height'];

    // タイムスタンプを無効にする
    public $timestamps = false;
}
