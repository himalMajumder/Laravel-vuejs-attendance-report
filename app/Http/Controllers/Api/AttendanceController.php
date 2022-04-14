<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AttendanceImport;
use App\Models\EmployeeAttendance;

class AttendanceController extends Controller
{

    public function all_employee_attendance(Request $request){

        $employeeAttendances = EmployeeAttendance::where(function($query){
            if(request()->id){
                $query->where("employee_id", request()->id);
            }
        })->get();
        return response()->json([
            'data' => $employeeAttendances
        ]);

    }

    public function import_attendance(Request $request){
        // Excel::import(new AttendanceImport, $request->file('file'));

        Excel::import(new AttendanceImport, $request->file('file'));

        $employeeAttendances = EmployeeAttendance::get();
        return response()->json([
            'data' => $employeeAttendances
        ]);

    }
}
