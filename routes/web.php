<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/seat_chart/seat_chart', 'seat_chart.seat_chart')->name('seat_chart.seat_chart');