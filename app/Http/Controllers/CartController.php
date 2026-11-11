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
        return new CartResource($cart->getFor($request->user(), $this->sessionId($request)));
    }

    public function addItem(StoreCartItemRequest $request, CartService $cart)
    {
        $data = $request->validated();
        $updatedCart = $cart->addItem(
            $request->user(),
            $this->sessionId($request),
            $data['manga_id'],
            $data['quantity'],
        );

        return new CartResource($updatedCart);
    }

    public function updateItem(UpdateCartItemRequest $request, int $cartItem, CartService $cart)
    {
        $updatedCart = $cart->updateItem(
            $request->user(),
            $this->sessionId($request),
            $cartItem,
            $request->validated('quantity'),
        );

        return new CartResource($updatedCart);
    }

    public function removeItem(Request $request, int $cartItem, CartService $cart)
    {
        $cart->removeItem($request->user(), $this->sessionId($request), $cartItem);

        return response()->noContent();
    }

    public function clear(Request $request, CartService $cart)
    {
        $cart->clear($request->user(), $this->sessionId($request));

        return response()->noContent();
    }

    private function sessionId(Request $request): string
    {
        return $request->session()->getId();
    }
}
