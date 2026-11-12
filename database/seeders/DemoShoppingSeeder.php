<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Manga;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoShoppingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $customer = User::where('email', 'cliente1@mangaverse.test')->firstOrFail();
        $manga = Manga::where('is_active', true)->where('stock', '>=', 5)->with('series')->firstOrFail();
        $address = $customer->addresses()->firstOrCreate(
            ['label' => 'Demonstração'],
            [
                'recipient_name' => $customer->name,
                'postal_code' => '80000-000',
                'state' => 'PR',
                'city' => 'Curitiba',
                'neighborhood' => 'Centro',
                'street' => 'Rua de Demonstração',
                'number' => '123',
                'is_default' => ! $customer->addresses()->exists(),
            ],
        );

        $userCart = $customer->cart()->firstOrCreate();
        $userCart->items()->firstOrCreate(['manga_id' => $manga->id], ['quantity' => 1]);

        $guestCart = Cart::create(['session_id' => Str::random(40)]);
        $guestCart->items()->create(['manga_id' => $manga->id, 'quantity' => 1]);

        foreach (['pending', 'paid', 'shipped', 'delivered', 'cancelled'] as $status) {
            $reference = "seed-demo-{$status}";

            if (Payment::where('transaction_reference', $reference)->exists()) {
                continue;
            }

            DB::transaction(function () use ($customer, $manga, $address, $status, $reference) {
                $product = Manga::with('series')->lockForUpdate()->findOrFail($manga->id);
                $total = Money::fromCents(Money::toCents((string) $product->price));
                $paid = in_array($status, ['paid', 'shipped', 'delivered'], true);

                $order = Order::create([
                    'user_id' => $customer->id,
                    'status' => $status,
                    'subtotal' => $total,
                    'shipping_cost' => '0.00',
                    'total' => $total,
                    'shipping_recipient' => $address->recipient_name,
                    'shipping_postal_code' => $address->postal_code,
                    'shipping_state' => $address->state,
                    'shipping_city' => $address->city,
                    'shipping_neighborhood' => $address->neighborhood,
                    'shipping_street' => $address->street,
                    'shipping_number' => $address->number,
                    'shipping_complement' => $address->complement,
                    'placed_at' => now(),
                ]);

                $order->items()->create([
                    'manga_id' => $product->id,
                    'manga_title' => $product->title ?: $product->series->title,
                    'volume_number' => $product->volume_number,
                    'isbn' => $product->isbn,
                    'unit_price' => $total,
                    'quantity' => 1,
                    'line_total' => $total,
                ]);

                $order->payments()->create([
                    'amount' => $total,
                    'method' => 'pix',
                    'status' => $paid ? 'paid' : $status,
                    'paid_at' => $paid ? now() : null,
                    'transaction_reference' => $reference,
                ]);

                if ($status !== 'cancelled') {
                    $product->decrement('stock');
                }
            });
        }
    }
}
