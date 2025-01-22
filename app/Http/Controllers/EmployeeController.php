<?php

namespace App\Http\Controllers;

use App\Exports\ExportEmployees;
use App\Imports\ImportEmployees;
use App\Traits\ApiResponse;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use DB;
use Excel;

class EmployeeController extends Controller
{
    //
    use ApiResponse;

    public function create(Request $request)
    {
        try {
            $request->validate([
                'employe_name' => 'required|string|max:255',
                'username' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'designation' => 'required',
            ]);
            DB::beginTransaction();

            $employee = Employee::create([
                'designation' => $request->designation	 ?? '',
                'status' => $request->status 	 ?? '',
                'phone' => $request->phone ?? '',
                'company_id' => auth('sanctum')->user()->company_id,
            ]);
            
            User::create([
                'name' => $request->employe_name,
                'email' => $request->email,
                'role' => 'employee',
                'company_id' => auth('sanctum')->user()->company_id,
                'employee_id' => $employee->id,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            return $this->successResponse(array('model' => 'employee'), 'Employee created successfully', [
                'employee' => $employee, 
            ]);

        } catch (\Exception $e) {
           DB::rollBack();
           return $this->errorResponse(['model' => 'employee'], $e->getMessage(), [], 422);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'employe_name' => 'required|string|max:255',
                'username' => 'required',
                'email' => 'required',
                'designation' => 'required',
                'employee_id' => 'required',
            ]);
            DB::beginTransaction();

            $employee = Employee::findOrFail($request->employee_id);
            $user = User::where('employee_id', $request->employee_id)->firstOrFail();

            $employee->update([
                'designation' => $request->designation	 ?? '',
                'status' => $request->status  ?? '',
                'phone' => $request->phone ?? '',
            ]);

            $user->update([
                'name' => $request->username,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $user->password,
            ]);

            DB::commit();

            return $this->successResponse(['model' => 'employee'], 'Employee updated successfully', [
                'employee' => $employee,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'employee'], $e->getMessage(), [], 422);
        }
    }

    public function delete($employeeId)
    {
        try {
            DB::beginTransaction();
            $employee = Employee::findOrFail($employeeId);
            $user = User::where('employee_id', $employeeId)->firstOrFail();

            $user->delete();
            $employee->delete();

            DB::commit();

            return $this->successResponse(['model' => 'company'], 'Employee deleted successfully', []);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'company'], $e->getMessage(), [], 422);
        }
    }

    public function getEmployee($employeeId)
    {
        try {

            $employee = DB::table('employees')
                ->join('users', 'employees.id', '=', 'users.employee_id')
                ->where('employees.id', $employeeId)
                ->select('employees.*', 'users.name as username', 'users.email')
                ->first();

            if (!$employee) {
                return $this->errorResponse(['model' => 'employee'], 'employee not found', [], 404);
            }

            return $this->successResponse(['model' => 'employee'], 'employee retrieved successfully', [
                'employee' => $employee,
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'employee'], $e->getMessage(), [], 422);
        }
    }

    public function getAll()
    {
        try {
            $employees = DB::table('employees')
                ->join('users', 'employees.id', '=', 'users.employee_id')
                ->select('employees.*', 'users.name as username', 'users.email')
                ->get();
            if ($employees->isEmpty()) {
                return $this->errorResponse(['model' => 'employee'], 'No employees found', [], 404);
            }
    
            return $this->successResponse(['model' => 'employee'], 'employees retrieved successfully', [
                'employees' => $employees,
            ]);
    
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'employee'], $e->getMessage(), [], 422);
        }
    }

    public function downloadSample()
    {
        $fileName = 'employee_sample.xlsx';
        return Excel::download(new ExportEmployees, $fileName);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048',
        ]);

        try {
            Excel::import(new ImportEmployees, $request->file('file'));

            return $this->successResponse(['model' => 'employee_import'], 'Employees imported successfully',[]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                ['model' => 'employee_import'],
                'Import failed due to an unexpected error.',
                [$e->getMessage()],
                500
            );
        }
    }


}
