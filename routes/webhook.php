<?php

use Illuminate\Support\Facades\Route;

Route::post('/telebot/{public_key}', 'TelebotController@callback');
