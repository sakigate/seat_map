<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});


Volt::route('/seat_map', 'seat_map.seat_map')->name('seats.index');