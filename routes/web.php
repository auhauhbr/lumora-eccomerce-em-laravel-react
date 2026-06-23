<?php

use Illuminate\Support\Facades\Route;

Route::view('/{caminho?}', 'aplicacao')
    ->where('caminho', '^(?!api|graphql|up).*$');
