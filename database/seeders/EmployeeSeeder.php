<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 部署IDを取得（例: department_id が主キー）
        $departmentIds = DB::table('departments')->pluck('department_id')->all();
        if (empty($departmentIds)) {
            // 部署が無ければ何もしない（外部キーエラー回避）
            return;
        }

        $faker = Faker::create('ja_JP');

        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = [
                'employee_name' => $faker->name(),                                  // 例: "山田 太郎"
                'department_id' => $departmentIds[array_rand($departmentIds)],     // ランダム割当（人数は自然にバラつく）
            ];
        }

        DB::table('employees')->insert($rows);
    }
}
