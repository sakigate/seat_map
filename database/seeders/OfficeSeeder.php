<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            ['office_name' => '在宅勤務'],
        ];

        DB::table('offices')->insert($param);
    }
}
