<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Hash;
use DB;

class CompanyController extends Controller
{
    //
    use ApiResponse;


    public function create(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string|max:255',
                'username' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
            ]);
            DB::beginTransaction();

            $company = Company::create([
                'company_name' => $request->company_name,
                'address' => $request->address ?? '',
                'phone' => $request->phone ?? '',
            ]);
            
            User::create([
                'name' => $request->username,
                'email' => $request->email,
                'role' => 'company',
                'company_id' => $company->id,
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            return $this->successResponse(array('model' => 'company'), 'Company and User created successfully', [
                'company' => $company, 
            ]);

        } catch (\Exception $e) {
           DB::rollBack();
           return $this->errorResponse(['model' => 'company'], $e->getMessage(), [], 422);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'company_name' => 'required|string|max:255',
                'username' => 'required',
                'email' => 'required|email',
                'company_id' => 'required',
            ]);
            DB::beginTransaction();

            $company = Company::findOrFail($request->company_id);
            $user = User::where('company_id', $request->company_id)->firstOrFail();

            $company->update([
                'company_name' => $request->company_name,
                'address' => $request->address ?? $company->address,
                'phone' => $request->phone ?? $company->phone,
            ]);

            $user->update([
                'name' => $request->username,
                'email' => $request->email,
                'password' => $request->password ? Hash::make($request->password) : $user->password,
            ]);

            DB::commit();

            return $this->successResponse(['model' => 'company'], 'Company and User updated successfully', [
                'company' => $company,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'company'], $e->getMessage(), [], 422);
        }
    }

    public function delete($companyId)
    {
        try {
            DB::beginTransaction();
            $company = Company::findOrFail($companyId);
            $user = User::where('company_id', $companyId)->firstOrFail();

            $user->delete();
            $company->delete();

            DB::commit();

            return $this->successResponse(['model' => 'company'], 'Company and User deleted successfully', []);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(['model' => 'company'], $e->getMessage(), [], 422);
        }
    }

    public function getCompany($companyId)
    {
        try {

            $company = DB::table('companies')
                ->join('users', 'companies.id', '=', 'users.company_id')
                ->where('companies.id', $companyId)
                ->select('companies.*', 'users.name as username', 'users.email')
                ->first();

            if (!$company) {
                return $this->errorResponse(['model' => 'company'], 'Company not found', [], 404);
            }

            return $this->successResponse(['model' => 'company'], 'Company retrieved successfully', [
                'company' => $company,
            ]);
            
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'company'], $e->getMessage(), [], 422);
        }
    }
    
    public function getAll()
    {
        try {
            $companies = DB::table('companies')
                ->join('users', 'companies.id', '=', 'users.company_id')
                ->select('companies.*', 'users.name as username', 'users.email')
                ->get();
            if ($companies->isEmpty()) {
                return $this->errorResponse(['model' => 'company'], 'No companies found', [], 404);
            }
    
            return $this->successResponse(['model' => 'company'], 'Companies retrieved successfully', [
                'companies' => $companies,
            ]);
    
        } catch (\Exception $e) {
            return $this->errorResponse(['model' => 'company'], $e->getMessage(), [], 422);
        }
    }
    

}
