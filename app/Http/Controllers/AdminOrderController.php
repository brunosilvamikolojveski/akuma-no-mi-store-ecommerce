<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminOrderIndexRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;

class AdminOrderController extends Controller
{
    public function index(AdminOrderIndexRequest $request, OrderService $orders)
    {
        return OrderResource::collection(
            $orders->listForAdmin($request->validated('status')),
        );
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $order, OrderService $orders)
    {
        return new OrderResource(
            $orders->updateStatusForAdmin($order, $request->validated('status')),
        );
    }
}
