<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api.token')->group(function (): void {
    Route::get('/ping', function () {
        return ['ok' => true];
    });

    Route::get('/hello', function () {
        return ['message' => 'Benji Buendia is a freshman barely surviving Apuma University, an elite institution infamous for its grueling, high-pressure workload. Against his judgment, he risked joining the student council, only to find that this supposed chaotic job turns suspiciously smooth. Every late-night paperwork is signed by the very second, every impossible deadline mysteriously moves through the dates, and every path to his success seems meticulously prepared by an unseen hand. The architect behind it all? His captivating, strictly off-limits council advisor. But this secret devotion is far from a selfless endeavor. As academic pressure turns into mounting tension, Benji learns that every favor his advisor grants comes at a steep price. It is a price that must be paid behind closed doors, until every "debt" is cleared on his name.'];
    });
});

Route::middleware('api.token')->group(function (): void {
    Route::get('/verity', function () {
        return ['message' => 'FUCK YOU SANTOS'];
    });
});
