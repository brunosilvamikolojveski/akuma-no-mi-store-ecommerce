<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('AdminUserSeeder só pode ser executado em local ou testing.');
            return;
        }

        $admin = config('admin.seed_user');

        if (blank($admin['name']) || blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('Defina ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD no .env local.');
            return;
        }

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Administrador'],
        );

        User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'role_id' => $role->id,
                'name' => $admin['name'],
                'password' => $admin['password'],
            ],
        );
    }
}
