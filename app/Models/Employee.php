<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'employee_id';
    protected $fillable = ['employee_name', 'department_id'];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }
}
