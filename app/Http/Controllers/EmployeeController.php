<?php

namespace App\Http\Controllers;

use App\Exports\ExportEmployees;
use App\Imports\ImportEmployees;
use App\Traits\ApiResponse;

use App\Models\Company;
use App\Models\Transaction;
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
                'benefit_amount' => ['nullable', 'numeric'],
            ]);
            DB::beginTransaction();

            $employee = Employee::create([
                'designation' => $request->designation	 ?? '',
                'status' => $request->status 	 ?? '',
                'phone' => $request->phone ?? '',
                'benefit_amount' => $request->benefit_amount ?? 0,
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
            if($request->benefit_amount){
                Transaction::create([
                    'employee_id' => $employee->id,
                    'transaction_type' => 'credit',
                    'amount' => $request->benefit_amount ?? '',
                    'balance' => $request->benefit_amount ?? '',
                    'description' => $request->description ?? '',
                ]);
            }

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
                ->where('employees.company_id', auth('sanctum')->user()->company_id)
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
        try {$companyId = auth('sanctum')->user()->company_id;

            $employees = DB::table('employees')
                    ->join('users as u1', 'employees.id', '=', 'u1.employee_id') 
                    ->join('users as u2', 'employees.company_id', '=', 'u2.company_id')
                    ->where('u2.role', 'company') // Filter for company role
                    ->select('employees.*', 'u1.name as employeename', 'u1.email','u2.name as companyname')
                    ->when($companyId, function ($query, $companyId) {
                        return $query->where('employees.company_id', $companyId);
                    })
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
                422
            );
        }
    }

    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|array',
            'employee_id.*' => 'exists:employees,id', 
            'amount' => 'required|numeric',
            'type' => 'required|in:credit,debit',
        ]);
        try {
            DB::beginTransaction(); 

            $transactions = [];
            foreach ($data['employee_id'] as $item) {
                $employee = Employee::findOrFail($item);
                $amount = $data['amount'];
                $type = $data['type'];

                if ($type === 'credit') {
                    $employee->benefit_amount += $amount;
                } else {
                    $employee->benefit_amount -= $amount;
                }
                $employee->save();

                $transaction = Transaction::create([
                    'employee_id' => $employee->id,
                    'amount' => $amount,
                    'balance' => $employee->benefit_amount,
                    'transaction_type' => $type,
                ]);

                $transactions[] = [
                    'employee_id' => $employee->id,
                    'transaction_id' => $transaction->id,
                    'status' => 'success',
                ];
            }

            DB::commit();
            return $this->successResponse(['model' => 'employee'], 'Transactions processed successfully', [
                'transactions' => $transactions,
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); 
            return $this->errorResponse(['model' => 'employee'], $e->getMessage(), [], 424);
        }
    }


}
