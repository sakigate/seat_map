<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $param = [
            ['department_name' => '営業部'],
            ['department_name' => '企画部'],
            ['department_name' => '総務部'],
            ['department_name' => '人事部'],
            ['department_name' => '経理部'],
            ['department_name' => 'システム部'],
            ['department_name' => 'マーケティング部'],
            ['department_name' => '生産部'],
            ['department_name' => '品質保証部'],
            ['department_name' => '開発部'],
        ];

        DB::table('departments')->insert($param);
    }
}
