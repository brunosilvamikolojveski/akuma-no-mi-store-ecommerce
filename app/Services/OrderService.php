<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Manga;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function listFor(User $user): Collection
    {
        return $user->orders()
            ->with(['items', 'payments'])
            ->orderByDesc('id')
            ->get();
    }

    public function findFor(User $user, int $orderId): Order
    {
        return $user->orders()
            ->with(['items', 'payments'])
            ->findOrFail($orderId);
    }

    public function createFromCart(User $user, int $addressId, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($user, $addressId, $paymentMethod) {
            $address = $user->addresses()->findOrFail($addressId);
            $cart = $user->cart()->lockForUpdate()->first();

            if (! $cart) {
                abort(422, 'O carrinho está vazio.');
            }

            $cartItems = $cart->items()
                ->orderBy('manga_id')
                ->lockForUpdate()
                ->get();

            if ($cartItems->isEmpty()) {
                abort(422, 'O carrinho está vazio.');
            }

            $mangas = Manga::query()
                ->whereKey($cartItems->pluck('manga_id'))
                ->with('series:id,title')
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;

            foreach ($cartItems as $cartItem) {
                $manga = $mangas->get($cartItem->manga_id);

                if (! $manga || ! $manga->is_active) {
                    abort(422, 'Um dos mangás do carrinho não está mais disponível.');
                }

                if ($cartItem->quantity > $manga->stock) {
                    abort(422, 'O estoque de um dos mangás não é suficiente para concluir o pedido.');
                }

                $subtotalCents += Money::toCents((string) $manga->price) * $cartItem->quantity;
            }

            $shippingCents = 0;
            $order = $user->orders()->create([
                'status' => 'pending',
                'subtotal' => Money::fromCents($subtotalCents),
                'shipping_cost' => Money::fromCents($shippingCents),
                'total' => Money::fromCents($subtotalCents + $shippingCents),
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

            foreach ($cartItems as $cartItem) {
                $manga = $mangas->get($cartItem->manga_id);
                $unitPriceCents = Money::toCents((string) $manga->price);

                $order->items()->create([
                    'manga_id' => $manga->id,
                    'manga_title' => $manga->title ?: $manga->series->title,
                    'volume_number' => $manga->volume_number,
                    'isbn' => $manga->isbn,
                    'unit_price' => Money::fromCents($unitPriceCents),
                    'quantity' => $cartItem->quantity,
                    'line_total' => Money::fromCents($unitPriceCents * $cartItem->quantity),
                ]);

                $manga->decrement('stock', $cartItem->quantity);
            }

            $order->payments()->create([
                'amount' => Money::fromCents($subtotalCents + $shippingCents),
                'method' => $paymentMethod,
                'status' => 'pending',
            ]);

            $cart->items()->delete();

            return $this->findFor($user, $order->id);
        });
    }
}
