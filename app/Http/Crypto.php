<?php

namespace App\Http;

class Crypto
{

    protected $configs;

    public function __construct()
    {
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->configs = array(
                //windows must import openssl configs
                "config" => $_ENV['OPENSSL_CONFIG_PATH_WINDOWS'],
                'private_key_bits' => 2048,
                'default_md' => "sha256",
            );
        } else {
            //linux must not import openssl configs
            $this->configs = null;
        }
    }

    public function genKeyPair(): array
    {
        $privateKey = openssl_pkey_new($this->configs);
        $publicKey_pem = openssl_pkey_get_details($privateKey)['key'];
        openssl_pkey_export($privateKey, $privateKey_pem, null, $this->configs);
        return [$publicKey_pem, $privateKey_pem];
    }
}
