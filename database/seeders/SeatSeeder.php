<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seat;
use App\Models\Office;

class SeatSeeder extends Seeder
{
    public function run()
    {
        // オフィスごとに座席レイアウトを定義
        $officeLayouts = [
            // オフィス1: 通常の格子状レイアウト (4x5)
            1 => [
                // [座席名, x位置, y位置, 幅, 高さ, レイアウト要素かどうか]
                ['役員席01', 1, 0, 2, 1, false],
                ['役員席02', 3, 0, 2, 1, false],
                ['席03', 0, 2, 1, 1, false],
                ['席04', 1, 2, 1, 1, false],
                ['席05', 2, 2, 1, 1, false],
                ['席06', 4, 2, 1, 1, false],
                ['席07', 5, 2, 1, 1, false],
                ['席08', 0, 3, 1, 1, false],
                ['席09', 1, 3, 1, 1, false],
                ['席10', 2, 3, 1, 1, false],
                ['席11', 4, 3, 1, 1, false],
                ['席12', 5, 3, 1, 1, false],
                ['席13', 0, 5, 1, 1, false],
                ['席14', 1, 5, 1, 1, false],
                ['席15', 2, 5, 1, 1, false],
                ['席16', 4, 5, 1, 1, false],
                ['席17', 5, 5, 1, 1, false],
                ['席18', 0, 6, 1, 1, false],
                ['席19', 1, 6, 1, 1, false],
                ['席20', 2, 6, 1, 1, false],
            ],
            2 => [
                // [座席名, x位置, y位置, 幅, 高さ, レイアウト要素かどうか]
                ['席01', 0, 0, 1, 1, false],
                ['席02', 1, 0, 1, 1, false],
                ['席03', 3, 0, 1, 1, false],
                ['席04', 4, 0, 1, 1, false],
                ['席05', 0, 1, 1, 1, false],
                ['席06', 1, 1, 1, 1, false],
                ['席07', 3, 1, 1, 1, false],
                ['席08', 4, 1, 1, 1, false],
                ['席09', 0, 2, 1, 1, false],
                ['席10', 1, 2, 1, 1, false],
                ['席11', 3, 2, 1, 1, false],
                ['席12', 4, 2, 1, 1, false],
                ['席13', 0, 3, 1, 1, false],
                ['席14', 1, 3, 1, 1, false],
                ['席15', 3, 3, 1, 1, false],
                ['席16', 4, 3, 1, 1, false],
                ['席17', 0, 4, 1, 1, false],
                ['席18', 1, 4, 1, 1, false],
                ['席19', 3, 4, 1, 1, false],
                ['席20', 4, 4, 1, 1, false],
            ],

            // オフィス2: 会議室レイアウト (6x4)
            3 => [
                // 中央に大きな会議テーブル
                ['会議テーブル', 1, 1, 2, 2, true],
                // 周囲に席を配置
                ['席01', 0, 0, 1, 1, false],
                ['席02', 1, 0, 1, 1, false],
                ['席03', 2, 0, 1, 1, false],
                ['席04', 3, 0, 1, 1, false],
                ['席05', 4, 0, 1, 1, false],
                ['席06', 5, 0, 1, 1, false],
                ['席07', 0, 1, 1, 1, false],
                ['席08', 5, 1, 1, 1, false],
                ['席09', 0, 2, 1, 1, false],
                ['席10', 5, 2, 1, 1, false],
                ['席11', 0, 3, 1, 1, false],
                ['席12', 1, 3, 1, 1, false],
                ['席13', 2, 3, 1, 1, false],
                ['席14', 3, 3, 1, 1, false],
                ['席15', 4, 3, 1, 1, false],
                ['席16', 5, 3, 1, 1, false],
                ['席17', 0, 4, 1, 1, false],
                ['席18', 1, 4, 1, 1, false],
                ['席19', 2, 4, 1, 1, false],
                ['席20', 3, 4, 1, 1, false],
            ],
            // オフィス3: アイランド型レイアウト (5x5)
            4 => [
                // 島型デスク1（左上）
                ['島1-席01', 0, 0, 1, 1, false],
                ['島1-席02', 1, 0, 1, 1, false],
                ['島1-席03', 0, 1, 1, 1, false],
                ['島1-席04', 1, 1, 1, 1, false],

                // 島型デスク2（右上）
                ['島2-席01', 3, 0, 1, 1, false],
                ['島2-席02', 4, 0, 1, 1, false],
                ['島2-席03', 3, 1, 1, 1, false],
                ['島2-席04', 4, 1, 1, 1, false],

                // 島型デスク3（中央）
                ['島3-席01', 1, 2, 1, 1, false],
                ['島3-席02', 2, 2, 1, 1, false],
                ['島3-席03', 3, 2, 1, 1, false],

                // 島型デスク4（左下）
                ['島4-席01', 0, 3, 1, 1, false],
                ['島4-席02', 1, 3, 1, 1, false],
                ['島4-席03', 0, 4, 1, 1, false],
                ['島4-席04', 1, 4, 1, 1, false],

                // 島型デスク5（右下）
                ['島5-席01', 3, 3, 1, 1, false],
                ['島5-席02', 4, 3, 1, 1, false],
                ['島5-席03', 3, 4, 1, 1, false],
                ['島5-席04', 4, 4, 1, 1, false],
            ],
        ];

        // オフィスごとのレイアウト設定
        $officeGridSizes = [
            1 => [6, 8], // [幅, 高さ]
            2 => [6, 8],
            3 => [6, 8],
            4 => [6, 8],
        ];

        // オフィスごとにレイアウトを作成
        $offices = Office::all();
        foreach ($offices as $office) {
            $officeId = $office->office_id;

            // オフィスのグリッドサイズを設定
            if (isset($officeGridSizes[$officeId])) {
                $office->update([
                    'layout_width' => $officeGridSizes[$officeId][0],
                    'layout_height' => $officeGridSizes[$officeId][1],
                ]);
            }

            // このオフィスのレイアウトがあれば作成
            if (isset($officeLayouts[$officeId])) {
                $layout = $officeLayouts[$officeId];

                foreach ($layout as $seatInfo) {
                    Seat::create([
                        'seat_name' => $seatInfo[0],
                        'office_id' => $officeId,
                        'x_position' => $seatInfo[1],
                        'y_position' => $seatInfo[2],
                        'width' => $seatInfo[3],
                        'height' => $seatInfo[4],
                        'is_layout_element' => $seatInfo[5],
                    ]);
                }
            } else {
                // レイアウト定義がないオフィスには標準的なグリッドを作成
                $gridWidth = 4;
                $gridHeight = 5;
                $office->update([
                    'layout_width' => $gridWidth,
                    'layout_height' => $gridHeight,
                ]);

                for ($i = 0; $i < 20; $i++) {
                    $seatNumber = $i + 1;
                    $seatName = "席" . str_pad($seatNumber, 2, '0', STR_PAD_LEFT);
                    $x = $i % $gridWidth;
                    $y = floor($i / $gridWidth);

                    Seat::create([
                        'seat_name' => $seatName,
                        'office_id' => $officeId,
                        'x_position' => $x,
                        'y_position' => $y,
                        'width' => 1,
                        'height' => 1,
                        'is_layout_element' => false,
                    ]);
                }
            }
        }
    }
}
