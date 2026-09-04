<?php

namespace Database\Seeders;

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
        $this->call(RoleSeeder::class);

        $admin = User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin Olimpiade',
            'email' => 'admin@olimpiade.test',
        ]);
        $admin->assignRole('admin');

        $this->call(ExamSeeder::class);
    }
}
