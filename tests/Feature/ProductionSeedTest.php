<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'production';
        config(['admin.seed_user' => [
            'name' => 'Admin de teste',
            'email' => 'admin@example.test',
            'password' => 'SenhaDeTeste123!',
        ]]);
    }

    public function test_production_does_not_create_demo_accounts_or_an_admin_by_default(): void
    {
        config(['admin.allow_production_seed' => false]);
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertExitCode(0);

        $this->assertSame(0, User::count());
        $this->assertDatabaseCount('mangas', 30);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('carts', 0);
    }

    public function test_production_admin_is_created_only_with_the_explicit_setting(): void
    {
        config(['admin.allow_production_seed' => true]);
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertExitCode(0);

        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role->name);
        $this->assertSame(1, User::count());
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('carts', 0);
    }
}
