<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function getFor(User $user): Cart
    {
        return $this->loadCart($this->cartFor($user));
    }

    public function addItem(User $user, int $mangaId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $mangaId, $quantity) {
            $manga = Manga::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($mangaId);

            $cart = $this->cartFor($user);
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

    public function updateItem(User $user, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($user, $itemId, $quantity) {
            $cart = $this->cartFor($user);
            $item = $cart->items()->findOrFail($itemId);
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

    public function removeItem(User $user, int $itemId): void
    {
        $cart = $this->cartFor($user);
        $cart->items()->findOrFail($itemId)->delete();
    }

    public function clear(User $user): void
    {
        $user->cart?->items()->delete();
    }

    private function cartFor(User $user): Cart
    {
        return $user->cart()->firstOrCreate([]);
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
