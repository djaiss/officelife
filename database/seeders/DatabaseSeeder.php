<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateCompany;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            email: 'michael.scott@dundermifflin.com',
            password: 'password',
            firstName: 'Michael',
            lastName: 'Scott',
        )->execute();

        User::factory()->count(5)->create([
            'company_id' => $company->id,
        ]);
    }
}
