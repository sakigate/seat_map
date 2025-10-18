<?php

use App\Models\Office;
use function Livewire\Volt\{state, mount};

state(['offices' => []]);

mount(function () {
    $this->offices = Office::orderBy('office_id')->get()->toArray();
});

?>

<div>
    <h1 class="font-bold text-lg mb-2">Offices</h1>
    <ul>
        @forelse ($offices as $office)
            <li>{{ $office['office_id'] }} ： {{ $office['office_name'] }}</li>
        @empty
            <li>データがありません</li>
        @endforelse
    </ul>
</div>
