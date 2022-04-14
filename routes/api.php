<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Imports\AttendanceImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/all_employee_a', function (Request $request) {
    return User::all();
});

Route::post('/all_employee_attendance', [App\Http\Controllers\Api\AttendanceController::class, 'all_employee_attendance']);


Route::post('/file_url', function (Request $request) {
    return asset('/attendance_formate.xlsx');
});

Route::post('/import_attendance', [App\Http\Controllers\Api\AttendanceController::class, 'import_attendance']);

