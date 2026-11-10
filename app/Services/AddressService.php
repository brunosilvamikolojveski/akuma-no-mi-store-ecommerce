<?php

namespace App\Services;

use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddressService
{
    public function listFor(User $user)
    {
        return $user->addresses()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }

    public function findFor(User $user, int $addressId): Address
    {
        return $user->addresses()->findOrFail($addressId);
    }

    public function createFor(User $user, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $attributes) {
            $isFirstAddress = ! $user->addresses()->exists();
            $makeDefault = $isFirstAddress || (bool) ($attributes['is_default'] ?? false);

            if ($makeDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $attributes['is_default'] = $makeDefault;

            return $user->addresses()->create($attributes);
        });
    }

    public function updateFor(User $user, int $addressId, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $addressId, $attributes) {
            $address = $this->findFor($user, $addressId);
            $wasDefault = $address->is_default;
            $makeDefault = (bool) ($attributes['is_default'] ?? false);

            if ($makeDefault) {
                $user->addresses()
                    ->where('id', '!=', $address->id)
                    ->update(['is_default' => false]);
            }

            $address->update($attributes);

            if ($wasDefault && array_key_exists('is_default', $attributes) && ! $makeDefault) {
                $nextDefault = $user->addresses()
                    ->where('id', '!=', $address->id)
                    ->orderBy('id')
                    ->first();

                $nextDefault?->update(['is_default' => true]);
            }

            return $address->refresh();
        });
    }

    public function deleteFor(User $user, int $addressId): void
    {
        DB::transaction(function () use ($user, $addressId) {
            $address = $this->findFor($user, $addressId);
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $nextDefault = $user->addresses()->orderBy('id')->first();
                $nextDefault?->update(['is_default' => true]);
            }
        });
    }
}
