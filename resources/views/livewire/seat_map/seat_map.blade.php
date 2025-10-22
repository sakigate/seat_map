<?php

use App\Models\{Office, Seat, CurrentSeat, Employee};
use Illuminate\Support\Facades\DB;
use function Livewire\Volt\{state, mount};

state([
    'officeId' => null,   // 表示中オフィス
    'seats' => [],     // 座席+現在の着席者
    'selectedEmpId' => null,   // 左で選んだ自分の社員ID
    'employeeQuery' => '',     // 社員検索キーワード
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
            'e.employee_id as occ_employee_id',
            'e.employee_name as occ_employee_name',
        ])
        ->leftJoin('current_seats as cs', 'cs.seat_id', '=', 'seats.seat_id')
        ->leftJoin('employees as e', 'e.employee_id', '=', 'cs.employee_id')
        ->where('seats.office_id', $this->officeId)
        ->orderBy('seats.seat_name')
        ->get()
        ->toArray();
};

$claimSeat = function (int $seatId) {
    if (!$this->selectedEmpId) {
        $this->dispatch('toast', body: '先に自分の名前（社員）を選択してください');
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
                'assigned_date' => now(),
            ]);
        });

        // 即時反映（ローカルstateを更新→画面にすぐ出す）
        foreach ($this->seats as &$seat) {
            if (($seat['occ_employee_id'] ?? null) === (int)$this->selectedEmpId) {
                $seat['occ_employee_id'] = null;
                $seat['occ_employee_name'] = null;
            }
        }
        unset($seat);

        $empName = optional(Employee::find($this->selectedEmpId))->employee_name;
        foreach ($this->seats as &$seat) {
            if ($seat['seat_id'] === $seatId) {
                $seat['occ_employee_id']   = (int)$this->selectedEmpId;
                $seat['occ_employee_name'] = $empName;
                break;
            }
        }
        unset($seat);

        $this->dispatch('toast', body: '着席しました');
    } catch (\Throwable $e) {
        $this->dispatch('toast', body: 'その席は既に使用中です');
    }

    // 最終整合（DB→UI）
    $this->refreshSeats();
};

$releaseSeat = function () {
    if (!$this->selectedEmpId) return;

    CurrentSeat::where('employee_id', $this->selectedEmpId)->delete();

    // 即時反映
    foreach ($this->seats as &$seat) {
        if (($seat['occ_employee_id'] ?? null) === (int)$this->selectedEmpId) {
            $seat['occ_employee_id']   = null;
            $seat['occ_employee_name'] = null;
        }
    }
    unset($seat);

    $this->refreshSeats();
    $this->dispatch('toast', body: '退席しました');
};

$clearSelectedEmployee = function () {
    $this->selectedEmpId = null;
};

?>
<div class="m-5">
    <h1 class="text-3xl font-bold text-center">オフィスマップ/座席表</h1>
    <div class="flex gap-6" wire:poll.5s="refreshSeats">
        <!-- 左：社員選択 -->
        <div class="w-50 space-y-3 m-5">
            <h3 class="font-semibold">自分の名前を選択</h3>

            <input type="text" placeholder="氏名で検索"
                wire:model.debounce.300ms="employeeQuery"
                class="w-full border rounded p-2" />

            <select wire:model="selectedEmpId" class="w-full border rounded p-2">
                <option value="">-- 選択してください --</option>
                @foreach(
                \App\Models\Employee::query()
                    ->when($employeeQuery, fn($q) => $q->where('employee_name','like',"%{$employeeQuery}%"))
                    ->orderBy('employee_name')->limit(100)->get() as $emp
                )
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
                    @foreach(\App\Models\Office::orderBy('office_name')->get() as $o)
                        <option value="{{ $o->office_id }}">{{ $o->office_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 中：座席グリッド -->
        <div class="border rounded p-3 sm:p-4 m-5">
            <div class="grid grid-cols-4 gap-2 sm:gap-3">
                @foreach($seats as $s)
                    @php
                        $occId = $s['occ_employee_id'] ?? null;
                        $occName = $s['occ_employee_name'] ?? null;
                        $disabled = !$selectedEmpId || ($occId && (int)$occId !== (int)$selectedEmpId);
                        $isMine = $occId && (int)$occId === (int)$selectedEmpId;
                        $bgClass = $isMine ? 'bg-blue-500' : ($occId ? 'bg-red-500' : 'bg-gray-400');
                    @endphp

                    <button
                        wire:key="seat-{{ $officeId }}-{{ $s['seat_id'] }}"
                        wire:click="claimSeat({{ $s['seat_id'] }})"
                        @disabled($disabled)
                        class="rounded text-white {{ $bgClass }}
                            flex flex-col items-center justify-center
                            w-full aspect-[3/2]
                            px-2 py-1.5
                            text-xs sm:text-sm leading-tight"
                        title="{{ $occId ? ('使用中: ' . ($occName ?? '')) : '空席' }}"
                    >
                        <div class="font-semibold text-[11px] sm:text-xs">{{ $s['seat_name'] }}</div>
                        @if($occId)
                            <div class="mt-0.5 text-[10px] sm:text-[11px] truncate w-full">{{ $occName }}</div>
                        @else
                            <div class="mt-0.5 text-[10px] sm:text-[11px] opacity-80">空席</div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>
</div>

