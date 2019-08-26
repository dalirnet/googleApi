<?php

namespace Firebase\JWT;

use DateTime;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

class JWT
{
    public static $leeway = 0;
    public static $supported_algs = array('HS256' => array('hash_hmac', 'SHA256'), 'HS512' => array('hash_hmac', 'SHA512'), 'HS384' => array('hash_hmac', 'SHA384'), 'RS256' => array('openssl', 'SHA256'),);

    public static function decode($jwt, $key, $allowed_algs = array())
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Key may not be empty');
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
            throw new UnexpectedValueException('Invalid header encoding');
        }
        if (null === $payload = JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64))) {
            throw new UnexpectedValueException('Invalid claims encoding');
        }
        $sig = JWT::urlsafeB64Decode($cryptob64);
        if (empty($header->alg)) {
            throw new DomainException('Empty algorithm');
        }
        if (empty(self::$supported_algs[$header->alg])) {
            throw new DomainException('Algorithm not supported');
        }
        if (!is_array($allowed_algs) || !in_array($header->alg, $allowed_algs)) {
            throw new DomainException('Algorithm not allowed');
        }
        if (is_array($key) || $key instanceof \ArrayAccess) {
            if (isset($header->kid)) {
                $key = $key[$header->kid];
            } else {
                throw new DomainException('"kid" empty, unable to lookup correct key');
            }
        }
        if (!JWT::verify("$headb64.$bodyb64", $sig, $key, $header->alg)) {
            throw new SignatureInvalidException('Signature verification failed');
        }
        if (isset($payload->nbf) && $payload->nbf > (time() + self::$leeway)) {
            throw new BeforeValidException('Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf));
        }
        if (isset($payload->iat) && $payload->iat > (time() + self::$leeway)) {
            throw new BeforeValidException('Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat));
        }
        if (isset($payload->exp) && (time() - self::$leeway) >= $payload->exp) {
            throw new ExpiredException('Expired token');
        }
        return $payload;
    }

    public static function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') && !(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {

            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        } else {

            $max_int_length = strlen((string)PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{' . $max_int_length . ',})/', ': "$1"', $input);
            $obj = json_decode($json_without_bigints);
        }
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            JWT::handleJsonError($errno);
        } elseif ($obj === null && $input !== 'null') {
            throw new DomainException('Null result with non-null input');
        }
        return $obj;
    }

    private static function handleJsonError($errno)
    {
        $messages = array(JSON_ERROR_DEPTH => 'Maximum stack depth exceeded', JSON_ERROR_CTRL_CHAR => 'Unexpected control character found', JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON');
        throw new DomainException(isset($messages[$errno]) ? $messages[$errno] : 'Unknown JSON error: ' . $errno);
    }

    public static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    private static function verify($msg, $signature, $key, $alg)
    {
        if (empty(self::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if (!$success) {
                    throw new DomainException("OpenSSL unable to verify data: " . openssl_error_string());
                } else {
                    return $signature;
                }
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists('hash_equals')) {
                    return hash_equals($signature, $hash);
                }
                $len = min(self::safeStrlen($signature), self::safeStrlen($hash));
                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (self::safeStrlen($signature) ^ self::safeStrlen($hash));
                return ($status === 0);
        }
    }

    private static function safeStrlen($str)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, '8bit');
        }
        return strlen($str);
    }

    public static function encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null)
    {
        $header = array('typ' => 'JWT', 'alg' => $alg);
        if ($keyId !== null) {
            $header['kid'] = $keyId;
        }
        if (isset($head) && is_array($head)) {
            $header = array_merge($head, $header);
        }
        $segments = array();
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($header));
        $segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($payload));
        $signing_input = implode('.', $segments);
        $signature = JWT::sign($signing_input, $key, $alg);
        $segments[] = JWT::urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    public static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') && $errno = json_last_error()) {
            JWT::handleJsonError($errno);
        } elseif ($json === 'null' && $input !== null) {
            throw new DomainException('Null result with non-null input');
        }
        return $json;
    }

    public static function sign($msg, $key, $alg = 'HS256')
    {
        if (empty(self::$supported_algs[$alg])) {
            throw new DomainException('Algorithm not supported');
        }
        list($function, $algorithm) = self::$supported_algs[$alg];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success) {
                    throw new DomainException("OpenSSL unable to sign data");
                } else {
                    return $signature;
                }
        }
    }
}
