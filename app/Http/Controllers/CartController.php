<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request, CartService $cart)
    {
        return new CartResource($cart->getFor($request->user()));
    }

    public function addItem(StoreCartItemRequest $request, CartService $cart)
    {
        $data = $request->validated();
        $updatedCart = $cart->addItem($request->user(), $data['manga_id'], $data['quantity']);

        return new CartResource($updatedCart);
    }

    public function updateItem(UpdateCartItemRequest $request, int $cartItem, CartService $cart)
    {
        $updatedCart = $cart->updateItem(
            $request->user(),
            $cartItem,
            $request->validated('quantity'),
        );

        return new CartResource($updatedCart);
    }

    public function removeItem(Request $request, int $cartItem, CartService $cart)
    {
        $cart->removeItem($request->user(), $cartItem);

        return response()->noContent();
    }

    public function clear(Request $request, CartService $cart)
    {
        $cart->clear($request->user());

        return response()->noContent();
    }
}
