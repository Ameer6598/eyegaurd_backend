<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportEmployees implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //
        return collect([
            ['John Doe', 'john.doe@example.com', 'password123', 'Manager'],
            ['Jane Smith', 'jane.smith@example.com', 'password456', 'Developer'],
        ]);
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Password', 'Designation'];
    }
}
