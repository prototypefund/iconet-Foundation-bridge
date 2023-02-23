<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Support\Facades\Validator;

class IconetPacket implements InvokableRule
{
    public const RULES = [
        '@context' => "required|in:https://ns.iconet-foundation.org#",
        '@type' => "required|in:Packet",
        '@id' => "required|url",
        'actor' => "required", // TODO can be string or array
        'to' => "required", // TODO can be string or array
        'interpreterManifests' => "required|array|min:1",
        'interpreterManifests.*.manifestUri' => "required|string", //TODO should be url
        'interpreterManifests.*.sourceTypes' => "required|array",
        'interpreterManifests.*.targetTypes' => "required|array",
        'interpreterManifests.*.sha-512' => "string", // TODO required
        'interpreterManifests.*.sha512' => "string",  // TODO serialize hyphens in mastodons note_serializer
        'content' => "required|array|min:1",
        'content.*.packetType' => "required|string",
        'content.*.payload' => "required", // TODO should be string?
    ];

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        Validator::make($value, self::RULES)->validate();
    }
}
