<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // ← これが正解

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $param = [
            ['office_name' => '東京オフィス'],
            ['office_name' => '大阪オフィス'],
        ];

        DB::table('offices')->insert($param);
    }
}
