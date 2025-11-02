<?php

use App\Models\{Office, Seat, CurrentSeat, Employee, Department};
use Illuminate\Support\Facades\DB;
use function Livewire\Volt\{state, mount, computed};

state([
    'officeId' => null, // 表示中オフィス
    'seats' => [], // オフィス＋座席+現在の着席者
    'selectedEmpId' => null, // 選択中の社員ID
    'departments' => [], // 部署一覧
    'offices' => [], // オフィス一覧
    'search' => '', // 社員検索キーワード
    'departmentFilter' => '', // 部署フィルター
    'currentOffice' => null, // 現在のオフィス
    'showEmployeeList' => false, // 社員名簿の表示
    'showPopup' => false, // ポップアップ表示フラグ
    'popupMessage' => '', // ポップアップメッセージ
    'popupType' => 'info', // ポップアップタイプ (info, success, error)
]);

mount(function () {
    $this->officeId = Office::query()->value('office_id');
    $this->departments = Department::all();
    $this->offices = Office::orderBy('office_name')->get();
    $this->refreshSeats();
    $this->updateCurrentOffice();
});

$updateCurrentOffice = function () {
    $this->currentOffice = Office::find($this->officeId);
};

$refreshSeats = function () {
    // seats を土台に current_seats / employees を LEFT JOIN（空席も出す）
    $this->seats = Seat::query()
        ->select(['seats.seat_id', 'seats.seat_name', 'seats.office_id', 'seats.x_position', 'seats.y_position', 'seats.width', 'seats.height', 'seats.is_layout_element', 'e.employee_id as occ_employee_id', 'e.employee_name as occ_employee_name'])
        ->leftJoin('current_seats as cs', 'cs.seat_id', '=', 'seats.seat_id')
        ->leftJoin('employees as e', 'e.employee_id', '=', 'cs.employee_id')
        ->leftJoin('offices as o', 'o.office_id', '=', 'cs.office_id')
        ->where('seats.office_id', $this->officeId)
        ->orderBy('seats.seat_name')
        ->get()
        ->toArray();

    $this->updateCurrentOffice();
};

$employees = function () {
    $query = Employee::query()->with('department');
    if ($this->search) {
        $query->where('employee_name', 'like', '%' . $this->search . '%');
    }
    if ($this->departmentFilter) {
        $query->where('department_id', $this->departmentFilter);
    }
    return $query->get();
};

$deptEmployees = computed(function () {
    return Employee::query()->with('department')->when($this->departmentFilter, fn($q) => $q->where('department_id', $this->departmentFilter))->when($this->search, fn($q) => $q->where('employee_name', 'like', "%{$this->search}%"))->orderBy('employee_name')->get();
});

$searchEmployees = function () {
    $this->showEmployeeList = true;
};

$clearEmployeeSearch = function () {
    $this->showEmployeeList = false;
    $this->search = '';
    $this->departmentFilter = '';
};

$getEmployeeSeatInfo = function () {
    if (!$this->selectedEmpId) {
        return null;
    }

    // 社員の現在の座席情報を取得
    $currentSeat = CurrentSeat::query()->where('employee_id', $this->selectedEmpId)->first();

    if (!$currentSeat) {
        return null;
    }

    // 座席とオフィス情報を取得
    $seat = Seat::find($currentSeat->seat_id);
    $office = Office::find($currentSeat->office_id);

    if (!$seat || !$office) {
        return null;
    }

    return [
        'seat_name' => $seat->seat_name,
        'office_name' => $office->office_name,
        'office_id' => $office->office_id,
    ];
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

        //ポップアップ表示
        $this->showPopup = true;
        $this->popupMessage = 'おはようございます😊今日も一日頑張りましょう！';
        $this->popupType = 'success';
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

//ポップアップを閉じる関数
$closePopup = function () {
    $this->showPopup = false;
    $this->popupMessage = '';
};

$clearSelectedEmployee = function () {
    $this->selectedEmpId = null;
};

$selectEmployee = function (int $employeeId) {
    $this->selectedEmpId = $employeeId;
};

?>

@php
    $gridwidth = $currentOffice->layout_width ?? 4;
    $gridheight = $currentOffice->layout_height > 0 ? $currentOffice->layout_height : ceil(count($seats) / $gridwidth);
@endphp
<div class="bg-[#00ced1]/30 p-4 rounded-lg relative">
    <!-- ポップアップ -->
    @if ($showPopup)
        <div class="absolute z-50" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <div
                class="bg-white rounded-lg shadow-xl p-4 max-w-md w-full border-2 {{ $popupType === 'error' ? 'border-red-400' : ($popupType === 'success' ? 'border-green-400' : 'border-blue-400') }}">
                <div class="flex justify-between items-center mb-2">
                    <h3
                        class="text-lg font-medium {{ $popupType === 'error' ? 'text-red-600' : ($popupType === 'success' ? 'text-green-600' : 'text-blue-600') }}">
                        {{ $popupType === 'error' ? 'エラー' : ($popupType === 'success' ? '出勤' : 'お知らせ') }}<br>
                    </h3>
                    <button wire:click="closePopup" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-3 flex items-center animate-bounce">
                    <img src="/images/image.png" alt="ポップアップ画像" class="h-16 w-16 mr-3">
                    <p class="text-gray-700">{{ $popupMessage }}</p>
                </div>
                <div class="flex justify-end">
                    <button wire:click="closePopup"
                        class="px-3 py-1 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 text-sm">
                        閉じる
                    </button>
                </div>
            </div>
        </div>
    @endif

    <h1 class="text-center m-5">
        <span class="bg-[#20b2aa]/50 text-5xl text-[#2f4f4f] font-bold p-2 rounded-lg">　あの人どこ？オフィスマップ　</span>
    </h1>
    <div class="m-5 w-full" wire:poll.5s="refreshSeats">
        <div class="flex justify-center w-full m-5">
            <!-- 左：オフィス選択と社員名簿 -->
            <div class="frex-1 p-3 m-5 bg-gray-50 rounded-lg shadow-sm">
                <!-- 左上：オフィス選択 -->
                <div class="space-y-3 frex">
                    <h2 class="text-2xl font-bold text-center mb-4 text-[#2f4f4f]">オフィス選択</h2>
                    <div class="mb-3">
                        <select wire:model="officeId" wire:change="refreshSeats" class="w-full border rounded p-2">
                            @foreach ($offices as $o)
                                <option value="{{ $o->office_id }}">{{ $o->office_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div><br>
                <!-- 左下：社員名簿 -->
                <h2 class="text-2xl font-bold text-center mb-4 text-[#2f4f4f]">社員名簿</h2>
                <div class="border border-gray-200 rounded relative overflow-y-auto frex">
                    <div class="p-3 sm:p-4">

                        <div class="mb-4 space-y-3">
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700">社員を検索</label>
                                <input type="text" wire:model="search" id="search"
                                    class="w-full border rounded p-2" placeholder="名前で検索...">
                            </div>
                            <!-- 名簿用の部署フィルター -->
                            <div>
                                <label for="departmentFilter"
                                    class="block text-sm font-medium text-gray-700">部署を選択</label>
                                <select id="departmentFilter" wire:model="departmentFilter"
                                    class="w-full border rounded p-2">
                                    <option value="">すべての部署</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->department_id }}">
                                            {{ $department->department_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 検索ボタン -->
                            <div class="w-full flex justify-center gap-2">
                                <button wire:click="searchEmployees"
                                    class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-[#5f9ea0]">
                                    検索
                                </button>
                                <button wire:click="clearEmployeeSearch"
                                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                                    クリア
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto max-h-96">
                        @if ($showEmployeeList)
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            社員名</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            部署</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            操作</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach ($this->deptEmployees as $employee)
                                        <tr
                                            class="{{ $selectedEmpId == $employee->employee_id ? 'bg-[#20b2aa]/10' : '' }}">
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center">
                                                    <div
                                                        class="h-8 w-8 rounded-full bg-[#008080] flex items-center justify-center text-white font-bold mr-2">
                                                        {{ mb_substr($employee->employee_name, 0, 1) }}
                                                    </div>
                                                    {{ $employee->employee_name }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                {{ $employee->department->department_name }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                <button wire:click="selectEmployee({{ $employee->employee_id }})"
                                                    class="bg-gray-400 px-2 py-1 {{ $selectedEmpId == $employee->employee_id ? 'bg-gray-600' : 'bg-[#2f4f4f]' }} text-white rounded text-xs hover:bg-[#2f4f4f]">
                                                    選択
                                                </button>
                                            </td>
                                        </tr>

                                        @if ($selectedEmpId == $employee->employee_id)
                                            <tr class="bg-[#20b2aa]/10">
                                                <td colspan="3" class="px-4 py-2">
                                                    <div class="flex flex-col space-y-2">
                                                        @php
                                                            $seatInfo = $this->getEmployeeSeatInfo();
                                                        @endphp

                                                        @if ($seatInfo)
                                                            <div class="text-xs text-gray-700">
                                                                <span class="font-medium">着席中:</span>
                                                                {{ $seatInfo['office_name'] }} -
                                                                {{ $seatInfo['seat_name'] }}
                                                            </div>
                                                        @endif

                                                        <div class="flex gap-2 mt-1">
                                                            <button wire:click="releaseSeat"
                                                                class="px-2 py-1 bg-gray-200 rounded text-xs hover:bg-gray-400">
                                                                退席する
                                                            </button>
                                                            <button wire:click="clearSelectedEmployee"
                                                                class="px-2 py-1 bg-gray-200 rounded text-xs hover:bg-gray-400">
                                                                選択解除
                                                            </button>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach

                                    @if (count($this->deptEmployees) === 0)
                                        <tr>
                                            <td colspan="3"
                                                class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 text-center">
                                                該当する社員が見つかりません</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                「検索」ボタンをクリックしてください
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- 右：座席表 -->
            <div class="frex-1 overflow-x-auto border border-gray-200 rounded-lg p-3 m-5 bg-gray-50 shadow-sm">
                <h2 class="text-[#2f4f4f] text-center text-3xl font-bold">{{ $currentOffice->office_name }}の座席表</h2>
                <div class="m-5 relative"
                    style="width: {{ $gridwidth * 100 }}px; height: {{ $gridheight * 80 }}px; transform-origin: top left;">
                    @foreach ($seats as $s)
                        @php
                            $occId = $s['occ_employee_id'] ?? null;
                            $occName = $s['occ_employee_name'] ?? null;
                            $disabled = !$selectedEmpId || ($occId && (int) $occId !== (int) $selectedEmpId);
                            $isMine = $occId && (int) $occId === (int) $selectedEmpId;
                            $bgClass = $isMine ? 'bg-[#20b2aa]' : ($occId ? 'bg-[#20b2aa]' : 'bg-gray-400');
                            $xPosition = $s['x_position'] ?? 0;
                            $yPosition = $s['y_position'] ?? 0;
                            $width = $s['width'] ?? 1;
                            $height = $s['height'] ?? 1;
                            $isLayoutElement = $s['is_layout_element'] ?? false;
                        @endphp
                        @if ($isLayoutElement)
                            <!-- レイアウト要素（クリックできない） -->
                            <div wire:key="layout-{{ $officeId }}-{{ $s['seat_id'] }}"
                                class="rounded-lg bg-slate-100 text-slate-800
                                border-2 border-slate-400
                                flex flex-col items-center justify-center
                                absolute
                                px-2 py-1.5
                                text-xs sm:text-sm leading-tight
                                shadow-md"
                                style="left: {{ $xPosition * 100 }}px; top: {{ $yPosition * 80 }}px; width: {{ $width * 100 - 8 }}px; height: {{ $height * 80 - 8 }}px;">
                                <div class="font-semibold text-[11px] sm:text-xs">{{ $s['seat_name'] }}
                                </div>
                                <div class="mt-0.5 text-[10px] sm:text-[11px] opacity-80">
                                </div>
                            </div>
                        @else
                            <!-- 通常の座席（クリック可能） -->
                            <button wire:key="seat-{{ $officeId }}-{{ $s['seat_id'] }}"
                                wire:click="claimSeat({{ $s['seat_id'] }})" @disabled($disabled)
                                class="rounded text-white {{ $bgClass }}
                                    flex flex-col items-center justify-center
                                    absolute
                                    px-2 py-1.5"
                                style="left: {{ $xPosition * 100 }}px; top: {{ $yPosition * 80 }}px; width: {{ $width * 100 - 8 }}px; height: {{ $height * 80 - 8 }}px;"
                                title="{{ $occId ? '使用中: ' . ($occName ?? '') : '空席' }}">
                                @if ($occId)
                                    <div class="mt-0.5 flex flex-col items-center">
                                        <div
                                            class="rounded-full h-10 w-10 bg-[#008080] flex items-center justify-center text-white font-bold text-[16px] mb-1">
                                            {{ mb_substr($occName, 0, 1) }}
                                        </div>
                                        <div class="text-[12px] truncate w-full text-center">{{ $occName }}</div>
                                    </div>
                                @else
                                    <div class="font-semibold text-[11px] sm:text-xs">{{ $s['seat_name'] }}
                                    </div>
                                    <div class="mt-0.5 text-[10px] sm:text-[11px] opacity-80">空席</div>
                                @endif
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
