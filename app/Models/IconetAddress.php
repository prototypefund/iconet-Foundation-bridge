<?php

namespace App\Models;

use ActivityPhp\Server;
use App\Services\Helpers;
use Illuminate\Support\Str;
use InvalidArgumentException;

class IconetAddress implements \JsonSerializable
{

    public const ENDPOINT = "iconet"; //Path for the iconet API that is appended to the domain part
    private const SEPARATOR = '@';
    private const PATTERN =
        "/^(?<local>[a-zA-Z][\w\.:-]*)" . self::SEPARATOR . "(?<domain>[a-zA-Z0-9-\.:]+)$/";

    public readonly string $local;
    public readonly string $domain;
    public readonly bool $isInternal;

    public function __construct(string $address)
    {
        $parts = self::parse($address);
        if (!$parts) {
            throw new InvalidArgumentException("Wrong address format: '$address'");
        }
        $this->local = $parts['local'];
        $this->domain = $parts['domain'];
        $this->isInternal = self::isInternal($this->domain);
    }

    /**
     * alice@example.net => alice__example.net@bridge.org
     * @return IconetAddress Returns a local address
     */
    public function bridged()
    {
        $bridgeDomain = env('APP_URL');
        return new IconetAddress("{$this->local}__{$this->domain}@$bridgeDomain");
    }

    /**
     * https://example.net/users/alice => alice@example.net
     * @param string $uri URI of an ActivityPub actor. Its server instance must support webfinger resource queries.
     * @return IconetAddress|null
     */
    public static function fromUri($uri)
    {
        $actor = Server::server()->actor($uri);
        $actorHandle = Helpers::getActorHandle($actor);
        if (!$actorHandle) return null;
        return new IconetAddress($actorHandle);
    }

    /**
     * Convert an actor uri of this bridge to an IconetAddress
     * https://bridge.org/user/alice__mastodon.org  => alice@mastodon.org
     * @param string $uri
     * @param bool $onlyInternal
     * @return IconetAddress|null
     */
    public static function fromBridgeUri(string $uri): ?IconetAddress
    {
        $userParam = Str::afterLast($uri, route('profile', '') . '/');
        $handle = Str::replace('__', '@', $userParam);
        if (!self::validate($handle)) return null;
        return new IconetAddress($handle);
    }

    public static function fromLocalUsername(string $username): ?IconetAddress
    {
        $address = $username . self::SEPARATOR . env('APP_URL');
        if (!self::validate($address)) {
            return null;
        }
        return new IconetAddress($address);
    }

    /**
     * @param string $address
     * @param bool $onlyInternal When true: Only allow addresses on this server
     * @return bool True, if the address format is valid
     */
    public static function validate(string $address, bool $onlyInternal = false): bool
    {
        return self::parse($address, $onlyInternal) != false;
    }

    /**
     * @param string $address
     * @param bool $onlyInternal When true: Only allow addresses on this server
     * @return bool|array<string> False or matched local/domain parts of the address
     */
    private static function parse(string $address, bool $onlyInternal = false): bool|array
    {
        $matchCount = preg_match(self::PATTERN, $address, $matches);
        $success =
            $matchCount === 1
            && (self::isDomain($matches['domain']))
            && (!$onlyInternal || self::isInternal($matches['domain']));

        return $success ? $matches : false;
    }

    private static function isInternal(string $domain): bool
    {
        return $domain === env('APP_URL');
    }

    //TODO be stricter
    private static function isDomain(string $domain): bool
    {
        return filter_var("https://" . $domain, FILTER_VALIDATE_URL);
    }

    public function getEndpoint(): string
    {
        $schema = env('DEBUG_DISABLE_HTTPS', false) ? "http://" : "https://";
        return $schema . $this->domain . '/' . self::ENDPOINT . '/';
    }

    public function __toString(): string
    {
        return $this->local . self::SEPARATOR . $this->domain;
    }

    public function jsonSerialize(): mixed
    {
        return (string)$this;
    }
}
