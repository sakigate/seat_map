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
        Schema::create('seats', function (Blueprint $table) {
            $table->increments("seat_id");
            $table->string("seat_name");
            $table->integer("office_id")->constrained('offices');
            $table->unique(['office_id', 'seat_name']); // 同一オフィス内で席コード一意

            $table->integer("x_position")->default(0);
            $table->integer("y_position")->default(0);
            $table->integer("width")->default(1);
            $table->integer("height")->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
