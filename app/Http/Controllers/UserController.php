<?php

namespace App\Http\Controllers;

use App\Services\UserManager;

class UserController extends Controller
{
    public function show(string $handle, UserManager $userManager)
    {
        $user = $userManager->findOrCreate($handle);

        return response()->json([
            "@context" => [
                "https://www.w3.org/ns/activitystreams",
                "https://w3id.org/security/v1"
            ],

            "id" => $user->uri(),
            "type" => "Person",
            "preferredUsername" => $user->handle,
            "inbox" => $user->inbox(),

            "publicKey" => [
                "id" => $user->keyId(),
                "owner" => $user->uri(),
                "publicKeyPem" => $user->publickey,
            ]
        ]);
    }

}
