<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, CartService $cart)
    {
        $guestSessionId = $request->session()->getId();
        $data = $request->validated();
        $customerRole = Role::firstOrCreate(
            ['name' => 'customer'],
            ['display_name' => 'Cliente'],
        );

        $user = User::create([
            'role_id' => $customerRole->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::guard('web')->login($user);
        $cartMerge = $cart->mergeGuestCartIntoUser($user, $guestSessionId);
        $request->session()->regenerate();

        return (new UserResource($user->load('role')))
            ->additional(['cart_merge' => $cartMerge])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request, CartService $cart)
    {
        $guestSessionId = $request->session()->getId();
        $credentials = $request->validated();

        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha incorretos.'],
            ]);
        }

        $user = $request->user();
        $cartMerge = $cart->mergeGuestCartIntoUser($user, $guestSessionId);
        $request->session()->regenerate();

        return (new UserResource($user->load('role')))
            ->additional(['cart_merge' => $cartMerge]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Sessão encerrada.']);
    }
}
