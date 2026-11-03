<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $customerRole = Role::firstOrCreate(
            ['name' => 'customer'],
            ['display_name' => 'Cliente'],
        );

        Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrador'],
        );

        User::factory()->create([
            'role_id' => $customerRole->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
