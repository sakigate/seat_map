<?php

use App\Models\Employee;
use App\Models\Department;
use Illuminate\Support\Collection;
use function Livewire\Volt\{state, computed};

state([
    'search' => '',
    'departmentFilter' => '',
    'departments' => Department::all(),
]);

$employees = computed(function () {
    $query = Employee::query()->with('department');
    if ($this->search) {
        $query->where('employee_name', 'like', '%' . $this->search . '%');
    }
    if ($this->departmentFilter) {
        $query->where('department_id', $this->departmentFilter);
    }
    return $query->get();
});

?>

<div>
    <h1 class="text-3xl font-bold text-center">社員名簿</h1>

    <div class="mb-6 flex space-x-4">
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700">社員を検索</label>
            <input type="text" wire:model="search" id="search" class="w-full border rounded p-2">
        </div>
    </div>

    <div>
        <label for="departmentFilter" class="block text-sm font-medium text-gray-700">部署を選択</label>
        <select id="department" wire:model="departmentFilter" class="w-full border rounded p-2">
            <option value="">-- 部署を選択 --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <table>
            <thead>
                <tr>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">社員名</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">部署</th>
                    </tr>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($employees as $employee)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $employee->employee_id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $employee->employee_name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $employee->department->department_name }}</td>
                    </tr>
                @endforeach

                @if(count($this->employees) === 0)
                    <tr>
                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">社員が見つかりません</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
