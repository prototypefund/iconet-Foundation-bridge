<?php

namespace App\Services;

use App\Http\Crypto;
use App\Models\User;

class UserManager
{
    public function findOrCreate(string $handle)
    {
        $user = User::find($handle);
        if (!$user) {
            $keypair = (new Crypto())->genKeyPair();
            $user = new User([
                'handle' => $handle,
                'publickey' => $keypair[0],
                'privatekey' => $keypair[1]
            ]);
            $user->save();
        }
        return $user;
    }
}
