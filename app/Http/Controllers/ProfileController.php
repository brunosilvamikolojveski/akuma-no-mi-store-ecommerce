<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return new UserResource($request->user()->load('role'));
    }

    public function update(UpdateUserRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return new UserResource($user->load('role'));
    }
}
