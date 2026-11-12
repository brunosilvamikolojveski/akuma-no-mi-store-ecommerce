<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoCustomerSeeder só pode ser executado em local ou testing.');
            return;
        }

        $role = Role::firstOrCreate(
            ['name' => 'customer'],
            ['display_name' => 'Cliente'],
        );

        foreach ([
            ['name' => 'Ana Cliente', 'email' => 'cliente1@mangaverse.test'],
            ['name' => 'Caio Cliente', 'email' => 'cliente2@mangaverse.test'],
            ['name' => 'Yumi Cliente', 'email' => 'cliente3@mangaverse.test'],
        ] as $customer) {
            User::updateOrCreate(
                ['email' => $customer['email']],
                $customer + [
                    'role_id' => $role->id,
                    'password' => 'cliente123',
                ],
            );
        }
    }
}
