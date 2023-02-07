<?php

namespace App\Services;

use ActivityPhp\Server\Actor;

class Helpers
{
    public static function getActorHandle(Actor $actor)
    {
        $port =  parse_url($actor->get('id'), PHP_URL_PORT);
        $portPrefix = is_null($port) ? "" : ":$port";

        return sprintf(
            '%s@%s%s',
            $actor->get('preferredUsername'),
            parse_url($actor->get('id'), PHP_URL_HOST),
            $portPrefix
        );
    }
}
