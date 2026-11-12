<?php

namespace App\Services;

use App\Models\Manga;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function listForAdmin(?string $status = null): Collection
    {
        return Order::query()
            ->with(['items', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->get();
    }

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

    public function cancelFor(User $user, int $orderId): Order
    {
        return DB::transaction(function () use ($user, $orderId) {
            $order = $user->orders()->lockForUpdate()->findOrFail($orderId);
            $this->cancelPendingOrder($order);

            return $this->findFor($user, $orderId);
        });
    }

    public function updateStatusForAdmin(int $orderId, string $newStatus): Order
    {
        return DB::transaction(function () use ($orderId, $newStatus) {
            $order = Order::query()->lockForUpdate()->findOrFail($orderId);

            if ($newStatus === 'cancelled') {
                $this->cancelPendingOrder($order);
            } elseif ($order->status === 'paid' && $newStatus === 'shipped') {
                $order->update(['status' => 'shipped']);
            } elseif ($order->status === 'shipped' && $newStatus === 'delivered') {
                $order->update(['status' => 'delivered']);
            } else {
                abort(409, 'Transição de status do pedido inválida.');
            }

            return $order->load(['items', 'payments']);
        });
    }

    public function confirmPayment(User $user, int $orderId): Order
    {
        return DB::transaction(function () use ($user, $orderId) {
            $order = $user->orders()->lockForUpdate()->findOrFail($orderId);
            $payment = $order->payments()->lockForUpdate()->orderByDesc('id')->first();

            if (! $payment) {
                abort(422, 'O pedido não possui pagamento.');
            }

            if ($payment->status === 'paid') {
                return $this->findFor($user, $orderId);
            }

            if ($payment->status !== 'pending') {
                abort(422, 'Este pagamento não pode ser confirmado.');
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $order->update(['status' => 'paid']);

            return $this->findFor($user, $orderId);
        });
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

    private function cancelPendingOrder(Order $order): void
    {
        if ($order->status !== 'pending') {
            abort(409, 'Somente pedidos aguardando pagamento podem ser cancelados.');
        }

        $items = $order->items()
            ->whereNotNull('manga_id')
            ->orderBy('manga_id')
            ->lockForUpdate()
            ->get();
        $mangas = Manga::query()
            ->whereKey($items->pluck('manga_id'))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $mangas->get($item->manga_id)?->increment('stock', $item->quantity);
        }

        $order->payments()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
        $order->update(['status' => 'cancelled']);
    }
}
