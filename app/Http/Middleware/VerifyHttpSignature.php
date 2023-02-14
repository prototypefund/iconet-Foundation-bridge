<?php

namespace App\Http\Middleware;

use ActivityPhp\Server;
use App\Http\HttpSignature;
use Closure;
use Illuminate\Http\Request;

class VerifyHttpSignature
{
    private static $keyId = null;

    /**
     * @return string? Returns the id of the public key that was used to sign this request.
     */
    public static function getKeyId()
    {
        return self::$keyId;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse) $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        [$verified, $signedString] = self::verifySignature($request);
        if (!$verified)  abort(401, "Signature of '$signedString' does not match");

        // TODO verify who signed, should be actor/author
        // TODO reject too old Date headers

        return $next($request);
    }

    private static function verifySignature(Request $request)
    {
        $signature = $request->headers->get('signature');
        if (!$signature) abort(401, 'No Signature in headers');

        $signatureData = HttpSignature::parseSignatureHeader($signature);
        self::$keyId = $signatureData['keyId'];

        $actor = Server::server()->actor(self::$keyId);
        if (!$actor)  abort(401, "Could not get actor for keyId " . self::$keyId);

        $publickey = $actor->getPublicKeyPem();
        if (!$publickey)  abort(401, "Could not get public key for keyId " . self::$keyId);

        return HttpSignature::verify(
            $publickey,
            $signatureData,
            $request->headers->all(),
            $request->getPathInfo(),
            $request->getContent()
        );
    }
}
