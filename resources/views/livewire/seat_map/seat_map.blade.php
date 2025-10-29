<?php

use App\Models\{Office, Seat, CurrentSeat, Employee};
use Illuminate\Support\Facades\DB;
use function Livewire\Volt\{state, mount};

state([
    'officeId' => null, // 表示中オフィス
    'seats' => [], // オフィス＋座席+現在の着席者
    'selectedEmpId' => null, // 左で選んだ自分の社員ID
    'employeeQuery' => '', // 社員検索キーワード
    'selectedDeptId' => null, // 選択した部署ID
]);

mount(function () {
    $this->officeId = Office::query()->value('office_id');
    $this->refreshSeats();
});

$refreshSeats = function () {
    // seats を土台に current_seats / employees を LEFT JOIN（空席も出す）
    $this->seats = Seat::query()
        ->select([
            'seats.seat_id',
            'seats.seat_name',
            'seats.office_id',
            'seats.x_position',
            'seats.y_position',
            'seats.width',
            'seats.height',
            'seats.is_layout_element',
            'e.employee_id as occ_employee_id',
            'e.employee_name as occ_employee_name'
        ])
        ->leftJoin('current_seats as cs', 'cs.seat_id', '=', 'seats.seat_id')
        ->leftJoin('employees as e', 'e.employee_id', '=', 'cs.employee_id')
        ->leftJoin('offices as o', 'o.office_id', '=', 'cs.office_id')
        ->where('seats.office_id', $this->officeId)
        ->orderBy('seats.seat_name')
        ->get()
        ->toArray();
};

$claimSeat = function (int $seatId) {
    if (!$this->selectedEmpId) {
        return;
    }

    try {
        DB::transaction(function () use ($seatId) {
            // 1) 既に自分が座っていたら外す（1人1席を維持）
            CurrentSeat::where('employee_id', $this->selectedEmpId)->delete();

            // 2) 席が埋まっていないか確認
            if (CurrentSeat::where('seat_id', $seatId)->exists()) {
                throw new \RuntimeException('その席は既に使用中です。');
            }

            // 3) 着席
            CurrentSeat::create([
                'seat_id' => $seatId,
                'employee_id' => $this->selectedEmpId,
                'office_id' => $this->officeId,
                'assigned_date' => now(),
            ]);
        });

        // 即時反映（ローカルstateを更新→画面にすぐ出す）
        foreach ($this->seats as &$seat) {
            if (($seat['occ_employee_id'] ?? null) === (int) $this->selectedEmpId) {
                $seat['occ_employee_id'] = null;
                $seat['occ_employee_name'] = null;
            }
        }
        unset($seat);

        $empName = optional(Employee::find($this->selectedEmpId))->employee_name;
        foreach ($this->seats as &$seat) {
            if ($seat['seat_id'] === $seatId) {
                $seat['occ_employee_id'] = (int) $this->selectedEmpId;
                $seat['occ_employee_name'] = $empName;
                break;
            }
        }
        unset($seat);
    } catch (\Throwable $e) {
    }

    // 最終整合（DB→UI）
    $this->refreshSeats();
};

$releaseSeat = function () {
    if (!$this->selectedEmpId) {
        return;
    }

    CurrentSeat::where('employee_id', $this->selectedEmpId)->delete();

    // 即時反映
    foreach ($this->seats as &$seat) {
        if (($seat['occ_employee_id'] ?? null) === (int) $this->selectedEmpId) {
            $seat['occ_employee_id'] = null;
            $seat['occ_employee_name'] = null;
        }
    }
    unset($seat);

    $this->refreshSeats();
};

$clearSelectedEmployee = function () {
    $this->selectedEmpId = null;
};

?>

@php
    $office = \App\Models\Office::find($officeId);
    $gridwidth = $office->layout_width ?? 4;
    $gridheight = $office->layout_height > 0 ? $office->layout_height : ceil(count($seats) / $gridwidth);
@endphp

<div class="m-5">
    <h1 class="text-3xl font-bold text-center">{{ $office->office_name }}の座席表</h1>
    <div class="flex gap-6" wire:poll.5s="refreshSeats">
        <!-- 左：社員選択 -->
        <div class="w-50 space-y-3 m-5">
            <h3 class="font-semibold">部署と自分の名前を選択</h3>

            <!-- 部署選択 -->
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">部署</label>
                <select wire:model="selectedDeptId" class="w-full border rounded p-2">
                    <option value="">-- 部署を選択 --</option>
                    @foreach (\App\Models\Department::orderBy('department_name')->get() as $dept)
                        <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 社員検索 -->
            <input type="text" placeholder="氏名で検索" wire:model.debounce.300ms="employeeQuery"
                class="w-full border rounded p-2" />
            <!-- 社員名選択 -->
            <select wire:model="selectedEmpId" class="w-full border rounded p-2">
                <option value="">-- 名前を選択 --</option>
                @foreach (\App\Models\Employee::query()->when($selectedDeptId, fn($q) => $q->where('department_id', $selectedDeptId))->when($employeeQuery, fn($q) => $q->where('employee_name', 'like', "%{$employeeQuery}%"))->orderBy('employee_name')->limit(100)->get() as $emp)
                    <option value="{{ $emp->employee_id }}">{{ $emp->employee_name }}</option>
                @endforeach
            </select>

            <div class="text-xs text-gray-600">
                選択中：
                <span class="font-medium">
                    {{ optional(\App\Models\Employee::find($selectedEmpId))->employee_name ?? '（未選択）' }}
                </span>
            </div>

            <div class="flex gap-2">
                <button wire:click="releaseSeat" class="px-3 py-2 bg-gray-200 rounded">退席する</button>
                <button wire:click="clearSelectedEmployee" class="px-3 py-2 bg-gray-100 rounded">選びなおす</button>
            </div>

            <div class="mt-4">
                <h4 class="font-semibold text-sm mb-2">オフィス</h4>
                <select wire:model="officeId" wire:change="refreshSeats" class="w-full border rounded p-2">
                    @foreach (\App\Models\Office::orderBy('office_name')->get() as $o)
                        <option value="{{ $o->office_id }}">{{ $o->office_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 中：座席グリッド -->
        <div class="border rounded p-3 sm:p-4 m-5">
            @php
                $office = \App\Models\Office::find($officeId);
                $gridwidth = $office->layout_width ?? 4;
                $gridheight = $office->layout_height > 0 ? $office->layout_height : ceil(count($seats) / $gridwidth);
            @endphp

            <div class="relative" style="width: {{ $gridwidth * 100 }}px; height: {{ $gridheight * 80 }}px;">
                @foreach ($seats as $s)
                    @php
                        $occId = $s['occ_employee_id'] ?? null;
                        $occName = $s['occ_employee_name'] ?? null;
                        $disabled = !$selectedEmpId || ($occId && (int) $occId !== (int) $selectedEmpId);
                        $isMine = $occId && (int) $occId === (int) $selectedEmpId;
                        $bgClass = $isMine ? 'bg-blue-500' : ($occId ? 'bg-red-500' : 'bg-gray-400');

                        $xPosition = $s['x_position'] ?? 0;
                        $yPosition = $s['y_position'] ?? 0;
                        $width = $s['width'] ?? 1;
                        $height = $s['height'] ?? 1;
                        $isLayoutElement = $s['is_layout_element'] ?? false;
                    @endphp

                    @if ($isLayoutElement)
                    <!-- レイアウト要素（クリックできない） -->
                        <div wire:key="layout-{{ $officeId }}-{{ $s['seat_id'] }}"
                            class="rounded-lg bg-gray-100 text-gray-800
                                border-2 border-gray-400
                                flex flex-col items-center justify-center
                                absolute
                                px-2 py-1.5
                                text-xs sm:text-sm leading-tight
                                shadow-md"
                            style="left: {{ $xPosition * 100 }}px; top: {{ $yPosition * 80 }}px; width: {{ $width * 100 - 8 }}px; height: {{ $height * 80 - 8 }}px;">
                            <div class="font-semibold text-[11px] sm:text-xs">{{ $s['seat_name'] }}</div>
                            <div class="mt-0.5 text-[10px] sm:text-[11px] opacity-80"></div>
                        </div>
                    @else
                    <!-- 通常の座席（クリック可能） -->
                    <button wire:key="seat-{{ $officeId }}-{{ $s['seat_id'] }}"
                        wire:click="claimSeat({{ $s['seat_id'] }})" @disabled($disabled)
                        class="rounded text-white {{ $bgClass }}
                            flex flex-col items-center justify-center
                            absolute
                            px-2 py-1.5
                            text-xs sm:text-sm leading-tight"
                        style="left: {{ $xPosition * 100 }}px; top: {{ $yPosition * 80 }}px; width: {{ $width * 100 - 8 }}px; height: {{ $height * 80 - 8 }}px;"
                        title="{{ $occId ? '使用中: ' . ($occName ?? '') : '空席' }}">
                        <div class="font-semibold text-[11px] sm:text-xs">{{ $s['seat_name'] }}</div>
                        @if ($occId)
                            <div class="mt-0.5 text-[10px] sm:text-[11px] truncate w-full">{{ $occName }}</div>
                        @else
                            <div class="mt-0.5 text-[10px] sm:text-[11px] opacity-80">空席</div>
                        @endif
                    </button>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
