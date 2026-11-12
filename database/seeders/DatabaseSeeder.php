<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Role::firstOrCreate(
            ['name' => 'customer'],
            ['display_name' => 'Cliente'],
        );

        Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrador'],
        );

        $this->call([
            CatalogSeeder::class,
            AdminUserSeeder::class,
            DemoCustomerSeeder::class,
            DemoShoppingSeeder::class,
        ]);
    }
}
