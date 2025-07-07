<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\RevisionController;
use App\Http\Controllers\Api\VersionController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\GroupPartController;
use App\Http\Controllers\Api\CodebuilderRuleController;
use App\Http\Controllers\Api\TypeController;
use App\Http\Controllers\Api\AdditionalFieldController;

// Parts
Route::apiResource('parts', PartController::class);

// Revisions
Route::apiResource('revisions', RevisionController::class);
Route::post('/revisions/create-from-version', [RevisionController::class, 'createFromVersion']);

// Versions
Route::apiResource('versions', VersionController::class);
Route::post('/versions/{id}/publish', [VersionController::class, 'publish']);

// Groups
Route::apiResource('groups', GroupController::class);

// Group Parts
Route::apiResource('group-parts', GroupPartController::class);

// Codebuilder Rules
Route::apiResource('codebuilder-rules', CodebuilderRuleController::class);

// Types
Route::apiResource('types', TypeController::class);

// Additional Fields
Route::apiResource('additional-fields', AdditionalFieldController::class);
