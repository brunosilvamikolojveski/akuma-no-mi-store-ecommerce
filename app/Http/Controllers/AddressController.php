<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request, AddressService $addresses)
    {
        return AddressResource::collection($addresses->listFor($request->user()));
    }

    public function store(StoreAddressRequest $request, AddressService $addresses)
    {
        $address = $addresses->createFor($request->user(), $request->validated());

        return (new AddressResource($address))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, int $address, AddressService $addresses)
    {
        return new AddressResource($addresses->findFor($request->user(), $address));
    }

    public function update(UpdateAddressRequest $request, int $address, AddressService $addresses)
    {
        $updated = $addresses->updateFor($request->user(), $address, $request->validated());

        return new AddressResource($updated);
    }

    public function destroy(Request $request, int $address, AddressService $addresses)
    {
        $addresses->deleteFor($request->user(), $address);

        return response()->noContent();
    }
}
