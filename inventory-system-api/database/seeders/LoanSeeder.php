<?php

namespace Database\Seeders;

use App\Helpers\IdGenerator;
use Illuminate\Database\Seeder;
use App\Models\Loan;

class LoanSeeder extends Seeder
{
    public function run(): void
    {
        Loan::create([
            'id_loan' => (IdGenerator::generateUniqueLoanId()),
            'id_asset' => 'Lap-0001',
            'id_user' => 2,
            'borrow_date' => now(),
            'due_date' => now()->addDays(7),
            'return_date' => null,
            'loan_status' => 'Dipinjam',
        ]);
    }
}
