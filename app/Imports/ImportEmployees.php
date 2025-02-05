<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Traits\ApiResponse;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportEmployees implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    use ApiResponse;
    private $errors = [];

    public function collection(Collection $rows)
    {
        $validatedRows = [];
        $rowNumber = 1; // Start row number for better error reporting

        foreach ($rows as $row) {
            $rowNumber++;
            if (empty($row['email']) || empty($row['password']) || empty($row['designation'])) {
                $this->errors[] = "Row {$rowNumber}: Missing required fields (email, password, designation).";
                continue;
            }

            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Row {$rowNumber}: Invalid email format ({$row['email']}).";
                continue;
            }

            if (User::where('email', $row['email'])->exists()) {
                $this->errors[] = "Row {$rowNumber}: Duplicate email found ({$row['email']}).";
                continue;
            }

            $validatedRows[] = $row; // Add valid rows to this array
        }

        // Stop further processing if errors exist
        if (!empty($this->errors)) {
            throw new \Exception("Validation errors found: \n" . implode("\n", $this->errors));
        }

        // Second Pass: Import all validated rows
        foreach ($validatedRows as $row) {
            $employee = Employee::create([
                'designation' => $row['designation'],
                'company_id' => auth('sanctum')->user()->company_id,

            ]);

            User::create([
                'name' => $row['name'],
                'email' => $row['email'],
                'password' => Hash::make($row['password']),
                'employee_id' => $employee->id,
                'role' => 'employee',
                'company_id' => auth('sanctum')->user()->company_id,

            ]);
        }
    }
}
