<?php

namespace App\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;


class HTTPClient
{
    /**
     * @throws RuntimeException When there is no valid http response.
     */
    public function send(string $url, string $message, array $headers=[]): string
    {
        $client = new Client(['timeout' => 2.0,]);

        try {
            $response = $client->post($url, ['body' => $message, 'query' => ['XDEBUG_SESSION_START'=>'13129'], 'headers' => $headers]);
        } catch (GuzzleException $ex) {
            throw new RuntimeException(
                "Got no valid http response from url '$url'",
                $ex->getCode(), $ex
            );
        }

        $code = $response->getStatusCode();

        if (!in_array( $code, [200, 201, 202])) throw new RuntimeException("Got https response $code from $url");

        return (string)$response->getBody();
    }
}
