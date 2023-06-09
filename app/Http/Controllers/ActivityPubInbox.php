<?php

namespace App\Http\Controllers;

use App\Services\HTTPClient;
use App\Models\IconetAddress;
use App\Rules\IconetPacket;
use App\Rules\SigningActor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class ActivityPubInbox extends Controller
{
    public function postActivity()
    {
        $activity = Request::all();
        self::validateRequest($activity);
        Log::debug(json_encode($activity));

        $object = $activity['object'];
        $receivers = array_merge($object['to'], $object['cc']);
        $actor = $activity['actor'];


        $iconet = $object['https://ns.iconet-foundation.org#iconet'] ?? $object['iconet'] ?? null;
        if (!is_array($iconet)) {
            Log::alert('WRONG ICONET format');
            $iconet = json_decode($iconet, JSON_OBJECT_AS_ARRAY);
        }
        if (!$iconet) {
            Log::alert('No iconet field set');
            abort(400, "No iconet field set");
        }

        Log::debug("Iconet", $iconet);

        $responses = self::send($actor, $receivers, $iconet);

        $print = $responses[0] ?? "empty";
        Log::debug("Responses: $print", $responses);

        return $responses;
    }


    /**
     * @throws RuntimeException When there is no valid http response.
     */
    private static function send($actor, array $toUris, $message): array
    {
        $actorAddress = IconetAddress::fromUri($actor)->bridged();
        $client = new HTTPClient();
        $responses = [];
        foreach ($toUris as $toUri) {
            $address = IconetAddress::fromBridgeUri($toUri);
            if (!$address) continue;
            Log::info("Sending to $address's inbox on " . $address->getEndpoint());
            $packet = array_merge($message,
                [
                    'to' => $address,
                    'actor' => $actorAddress,
                    '@context' => 'https://static.iconet-foundation.org/ns#',
                ]);

            Log::info("Packet is ", $packet);

            $responses[$address->getEndpoint()] = $client->send($address->getEndpoint(), json_encode($packet));
        }
        return $responses;
    }


    private static function validateRequest($activity)
    {
        Validator::make($activity, [
            '@context'  => "required",
            'type'      => "required|in:Create",
            'id'        => "required|url",
            'actor'     => ["required", "string", new SigningActor],
            'to'        => "required|array",
            'published' => "required|date",
            'object'         => "required|array",
            'object.type'    => "required|in:Note",
            'object.to'      => "required|array",
            'object.iconet'  => ['required', new IconetPacket],
        ])->validate();
    }
}
