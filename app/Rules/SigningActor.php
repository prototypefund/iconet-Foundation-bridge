<?php

namespace App\Rules;

use ActivityPhp\Server;
use App\Http\Middleware\VerifyHttpSignature;
use Illuminate\Contracts\Validation\InvokableRule;

class SigningActor implements InvokableRule
{
    const INVALID_ACTOR = "The :attribute is not a valid ActivityPub actor of a server instance with webfinger.";
    const NOT_THE_SIGNER = "The :attribute is not the signer of this request.";
    protected $data = [];

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
        try {
            $actor = Server::server()->actor($value);
        } catch (\Exception $e) {
            $fail(self::INVALID_ACTOR);
            return;
        }


        $key = $actor->get('publicKey');
        if (VerifyHttpSignature::getKeyId() !== $key['id']) {
            $fail(self::NOT_THE_SIGNER);
        }
    }
}
