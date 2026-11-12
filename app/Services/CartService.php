<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getFor(?User $user, string $sessionId): Cart
    {
        return $this->loadCart($this->findOrCreateCart($user, $sessionId));
    }

    public function addItem(?User $user, string $sessionId, int $mangaId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $sessionId, $mangaId, $quantity) {
            $manga = Manga::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($mangaId);

            $cart = $this->findOrCreateCart($user, $sessionId);
            $item = $cart->items()
                ->where('manga_id', $manga->id)
                ->lockForUpdate()
                ->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            if ($newQuantity > $manga->stock) {
                abort(422, 'A quantidade solicitada é maior que o estoque disponível.');
            }

            if ($item) {
                $item->update(['quantity' => $newQuantity]);
            } else {
                $cart->items()->create([
                    'manga_id' => $manga->id,
                    'quantity' => $newQuantity,
                ]);
            }

            return $this->loadCart($cart);
        });
    }

    public function updateItem(?User $user, string $sessionId, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $sessionId, $itemId, $quantity) {
            $cart = $this->findOrCreateCart($user, $sessionId);
            $item = $cart->items()->findOrFail($itemId);

            if ($quantity === 0) {
                $item->delete();

                return $this->loadCart($cart);
            }

            $manga = Manga::query()->lockForUpdate()->findOrFail($item->manga_id);

            if (! $manga->is_active) {
                abort(422, 'Este mangá não está mais disponível para venda.');
            }

            if ($quantity > $manga->stock) {
                abort(422, 'A quantidade solicitada é maior que o estoque disponível.');
            }

            $item->update(['quantity' => $quantity]);

            return $this->loadCart($cart);
        });
    }

    public function removeItem(?User $user, string $sessionId, int $itemId): void
    {
        $cart = $this->findOrCreateCart($user, $sessionId);
        $cart->items()->findOrFail($itemId)->delete();
    }

    public function clear(?User $user, string $sessionId): void
    {
        $this->findCart($user, $sessionId)?->items()->delete();
    }

    public function mergeGuestCartIntoUser(User $user, string $guestSessionId): array
    {
        return DB::transaction(function () use ($user, $guestSessionId) {
            $guestCart = Cart::query()
                ->where('session_id', $guestSessionId)
                ->lockForUpdate()
                ->first();

            if (! $guestCart) {
                return ['adjusted_items' => [], 'removed_items' => []];
            }

            $userCart = $user->cart()->lockForUpdate()->first();

            if (! $userCart) {
                $userCart = $user->cart()->create();
            }

            $guestItems = $guestCart->items()
                ->orderBy('manga_id')
                ->lockForUpdate()
                ->get();
            $adjustedItems = [];
            $removedItems = [];

            foreach ($guestItems as $guestItem) {
                $manga = Manga::query()
                    ->with('series:id,title')
                    ->lockForUpdate()
                    ->findOrFail($guestItem->manga_id);
                $userItem = $userCart->items()
                    ->where('manga_id', $manga->id)
                    ->lockForUpdate()
                    ->first();

                if (! $manga->is_active || $manga->stock < 1) {
                    $removedItems[] = [
                        'manga_id' => $manga->id,
                        'title' => $manga->title ?: $manga->series->title,
                    ];

                    continue;
                }

                $requestedQuantity = ($userItem?->quantity ?? 0) + $guestItem->quantity;
                $finalQuantity = min($requestedQuantity, $manga->stock);

                if ($finalQuantity < $requestedQuantity) {
                    $adjustedItems[] = [
                        'manga_id' => $manga->id,
                        'title' => $manga->title ?: $manga->series->title,
                        'requested_quantity' => $requestedQuantity,
                        'kept_quantity' => $finalQuantity,
                    ];
                }

                if ($userItem) {
                    $userItem->update(['quantity' => $finalQuantity]);
                } else {
                    $userCart->items()->create([
                        'manga_id' => $manga->id,
                        'quantity' => $finalQuantity,
                    ]);
                }
            }

            $guestCart->delete();

            return [
                'adjusted_items' => $adjustedItems,
                'removed_items' => $removedItems,
            ];
        });
    }

    private function findOrCreateCart(?User $user, string $sessionId): Cart
    {
        if ($user) {
            return $user->cart()->firstOrCreate([]);
        }

        return Cart::query()->firstOrCreate(['session_id' => $sessionId]);
    }

    private function findCart(?User $user, string $sessionId): ?Cart
    {
        if ($user) {
            return $user->cart()->first();
        }

        return Cart::query()->where('session_id', $sessionId)->first();
    }

    private function loadCart(Cart $cart): Cart
    {
        return $cart->load([
            'items.manga.series.publisher',
            'items.manga.series.authors:id,name',
            'items.manga.series.categories:id,name,slug',
        ]);
    }
}
