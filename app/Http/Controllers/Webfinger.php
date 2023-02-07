<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class Webfinger extends Controller
{
    public function query()
    {
        $acct = Str::of(Request::input('resource'));
        abort_if(!$acct->startsWith('acct:'), 404);
        $handle = Str::between($acct, 'acct:', '@');


        $webfinger = [
            "subject" => "$acct",
            "links" => [
                [
                    "rel" => "self",
                    "type" => "application/activity+json",
                    "href" => route('profile', $handle)
                ],
            ]
        ];
        return response()->json($webfinger);
    }
}
