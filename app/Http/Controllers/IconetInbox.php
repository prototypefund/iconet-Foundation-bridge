<?php

namespace App\Http\Controllers;

use ActivityPhp\Server;
use ActivityPhp\Type;
use App\Http\HttpSignature;
use App\Services\HTTPClient;
use App\Models\IconetAddress;
use App\Models\User;
use App\Rules\IconetPacket;
use App\Services\UserManager;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IconetInbox extends Controller
{

    public function post( UserManager $userManager)
    {
        $iconetPacket = Request::all();
        Validator::make($iconetPacket, IconetPacket::RULES)->validate();

        [$actor, $to, $activity] = $this->buildActivity($iconetPacket);

        return $this->signAndDeliver($actor, $to, $activity->toJson());
    }

    /**
     * @param array $iconetPacket
     * @param UserManager $userManager
     * @return array
     */
    private function buildActivity(array $iconetPacket): array
    {
        $iconetActor = $iconetPacket['actor'];
        $iconetTo = $iconetPacket['to'];

        $actorBridgedAddress = (new IconetAddress($iconetActor))->bridged();
        $actor = app(UserManager::class)->findOrCreate($actorBridgedAddress->local);

        $toHandle = (new IconetAddress($iconetTo))->unbridged();
        if (!$toHandle) abort(400, "The addressed user's home server ('$iconetTo') is not on this bridge.");
        $to = Server::server()->actor($toHandle);

        $iconetPacket = self::translateIconetPacket($iconetPacket, $actorBridgedAddress, $toHandle);
        $activity = self::wrapInCreateActivity($iconetPacket, $actor->uri(), $to->get('id'));

        return [$actor, $to, $activity];
    }

    private function signAndDeliver(User $actor, Server\Actor $to, string $body): string
    {
        $headers = HttpSignature::sign($actor->privatekey, $actor->keyId(), $to->get('inbox'), $body);
        return (new HTTPClient())->send($to->get('inbox'), $body, $headers);
    }


    private static function wrapInCreateActivity(array $iconetPacket, string $actor, string $to)
    {
        return Type::create([
            "@context" => "https://www.w3.org/ns/activitystreams",
            "id" => self::fakeUri(),
            "type" => "Create",
            "actor" => $actor,

            "object" => [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                "id" => self::fakeUri(),
                "type" => "Note",
                "published" => date(DATE_RFC3339),
                "content" => "This status contains iconet data.",
                "to" => [$to,
                    "https://www.w3.org/ns/activitystreams#Public"], // Only public toots show up in the feed
                "https://ns.iconet-foundation.org#iconet" => $iconetPacket,
            ]
        ]);
    }

    // FIXME
    private static function fakeUri()
    {
        return 'https://' . env('APP_URL') . '/' . Str::uuid();
    }

    /* These fields are redundant,
     * because the corresponding fields in the Create Activity already handle packet transport.
     * We could remove them for now, but they might be needed for client interactions later.
     */
    private static function translateIconetPacket(array $packet, string $actor, string $to)
    {
        $packet["@id"] = self::fakeUri();
        $packet["actor"] = $actor;
        $packet["to"] = [$to];
        return $packet;
    }
}
