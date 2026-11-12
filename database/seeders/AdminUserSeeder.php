<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('admin.allow_production_seed')) {
            $this->command?->warn('Para criar o admin em produção, habilite ADMIN_SEED_ENABLED e configure as credenciais.');
            return;
        }

        $admin = config('admin.seed_user');

        if (blank($admin['name']) || blank($admin['email']) || blank($admin['password'])) {
            $this->command?->warn('Configure ADMIN_NAME, ADMIN_EMAIL e ADMIN_PASSWORD nas variáveis do ambiente.');
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
