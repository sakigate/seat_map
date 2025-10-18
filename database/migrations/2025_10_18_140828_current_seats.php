<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('current_seats', function (Blueprint $table) {
            $table->increments('current_id');
            $table->integer('seat_name')->constrained('seats');
            $table->integer('employee_name')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('assigned_date'); //利用日
            $table->unique('seat_name');      // 1つの席に2人は不可
            $table->unique('employee_name');  // 1人が同時に2席は不可
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
