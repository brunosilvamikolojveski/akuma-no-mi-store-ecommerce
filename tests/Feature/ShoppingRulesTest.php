<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Manga;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ShoppingRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_zero_quantity_removes_an_unavailable_item(): void
    {
        $user = User::where('email', 'cliente1@mangaverse.test')->firstOrFail();
        $item = $user->cart->items()->firstOrFail();
        $item->manga->update(['is_active' => false]);

        $this->actingAs($user)->withHeader('Origin', 'http://localhost:8000')
            ->patchJson("/api/cart/items/{$item->id}", ['quantity' => 0])
            ->assertOk()->assertJsonCount(0, 'data.items');

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_a_user_cannot_have_two_carts_but_visitors_can(): void
    {
        Cart::create(['session_id' => 'another-guest-session']);
        $user = User::where('email', 'cliente1@mangaverse.test')->firstOrFail();

        $this->expectException(QueryException::class);
        Cart::create(['user_id' => $user->id]);
    }

    public function test_payment_requires_ownership_and_cannot_be_repeated(): void
    {
        $order = Order::where('status', 'pending')->firstOrFail();
        $otherUser = User::where('email', 'cliente2@mangaverse.test')->firstOrFail();
        $this->actingAs($otherUser)->postJson("/api/orders/{$order->id}/confirm-payment")->assertForbidden();

        $this->actingAs($order->user)->postJson("/api/orders/{$order->id}/confirm-payment")
            ->assertOk()->assertJsonPath('data.status', 'paid');
        $this->assertNotNull($order->payments()->first()->paid_at);
        $this->postJson("/api/orders/{$order->id}/confirm-payment")->assertStatus(409);
    }

    public function test_checkout_failure_identifies_the_product_and_preserves_stock_and_cart(): void
    {
        $user = User::where('email', 'cliente1@mangaverse.test')->firstOrFail();
        $item = $user->cart->items()->firstOrFail();
        $manga = $item->manga;
        $manga->update(['stock' => 0]);
        $count = Order::count();

        try {
            app(OrderService::class)->createFromCart($user, $user->addresses()->first()->id, 'pix');
            $this->fail('O pedido deveria ser bloqueado por falta de estoque.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertStringContainsString($manga->title ?: $manga->series->title, $exception->getMessage());
        }

        $this->assertSame($count, Order::count());
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
        $this->assertSame(0, $manga->fresh()->stock);
    }

    public function test_merge_caps_quantity_reports_adjustment_and_discards_guest_cart(): void
    {
        $user = User::where('email', 'cliente1@mangaverse.test')->firstOrFail();
        $item = $user->cart->items()->firstOrFail();
        $item->manga->update(['stock' => 2]);
        $guest = Cart::create(['session_id' => 'guest-to-merge']);
        $guest->items()->create(['manga_id' => $item->manga_id, 'quantity' => 2]);

        $result = app(CartService::class)->mergeGuestCartIntoUser($user, 'guest-to-merge');

        $this->assertSame(2, $item->fresh()->quantity);
        $this->assertSame(3, $result['adjusted_items'][0]['requested_quantity']);
        $this->assertSame(2, $result['adjusted_items'][0]['kept_quantity']);
        $this->assertDatabaseMissing('carts', ['id' => $guest->id]);
        $this->assertSame(30, Manga::count());
        $this->assertSame(5, Order::distinct()->count('status'));
    }
}
