<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request, OrderService $orders)
    {
        return OrderResource::collection($orders->listFor($request->user()));
    }

    public function store(StoreOrderRequest $request, OrderService $orders)
    {
        $data = $request->validated();
        $order = $orders->createFromCart(
            $request->user(),
            $data['address_id'],
            $data['payment_method'],
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $order, OrderService $orders)
    {
        return new OrderResource($orders->findFor($request->user(), $order));
    }

    public function confirmPayment(Request $request, int $order, OrderService $orders)
    {
        return new OrderResource($orders->confirmPayment($request->user(), $order));
    }
}
