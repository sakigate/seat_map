<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // offices の主キーが office_id の想定（id の場合は pluck('id') に変更）
        $officeIds = DB::table('offices')->orderBy('office_id')->pluck('office_id')->all();

        $rows = [];
        $letters = range('A', 'Z'); // A, B, C, ...

        foreach ($officeIds as $idx => $officeId) {
            $prefix = $letters[$idx % 26]; // 27件超は A に戻る
            for ($i = 1; $i <= 20; $i++) {
                $rows[] = [
                    'seat_name' => sprintf('%s-%02d', $prefix, $i),
                    'office_id' => $officeId,
                ];
            }
        }

        if ($rows) {
            DB::table('seats')->insert($rows);
        }
    }
}
