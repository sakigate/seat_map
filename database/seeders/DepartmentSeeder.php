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
            ['department_name' => '経営企画室'],
            ['department_name' => '監査室'],
            ['department_name' => 'プロダクトビジネス本部'],
            ['department_name' => 'グローバル営業本部'],
            ['department_name' => 'グローバルオペレーション本部'],
            ['department_name' => '開発本部'],
            ['department_name' => '生産本部'],
            ['department_name' => '品質保証本部'],
            ['department_name' => '人財総務本部'],
            ['department_name' => '研修所'],
        ];

        DB::table('departments')->insert($param);
    }
}
