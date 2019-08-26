<?php

namespace Google\Api\Http;

use Google\Api\Service\GoogleServiceException;
use Google\Api\Task\GoogleTaskRunner;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Psr7\Response;
use Psr\HttpMessage\RequestInterface;
use Psr\HttpMessage\ResponseInterface;

class GoogleHttpREST
{
    public static function execute(ClientInterface $client, RequestInterface $request, $expectedClass = null, $config = array(), $retryMap = null)
    {
        $runner = new GoogleTaskRunner($config, sprintf('%s %s', $request->getMethod(), (string)$request->getUri()), array(get_class(), 'doExecute'), array($client, $request, $expectedClass));
        if (!is_null($retryMap)) {
            $runner->setRetryMap($retryMap);
        }
        return $runner->run();
    }

    public static function doExecute(ClientInterface $client, RequestInterface $request, $expectedClass = null)
    {
        try {
            $httpHandler = HttpHandlerFactory::build($client);
            $response = $httpHandler($request);
        } catch (RequestException $e) {

            if (!$e->hasResponse()) {
                throw $e;
            }
            $response = $e->getResponse();
            if ($response instanceof \GuzzleHttp\Message\ResponseInterface) {
                $response = new Response($response->getStatusCode(), $response->getHeaders() ?: [], $response->getBody(), $response->getProtocolVersion(), $response->getReasonPhrase());
            }
        }
        return self::decodeHttpResponse($response, $request, $expectedClass);
    }

    public static function decodeHttpResponse(ResponseInterface $response, RequestInterface $request = null, $expectedClass = null)
    {
        $code = $response->getStatusCode();
        if ((intVal($code)) >= 400) {

            $body = (string)$response->getBody();
            echo $body;
            exit;
            throw new GoogleServiceException($body, $code, null, self::getResponseErrors($body));
        }
        $body = self::decodeBody($response, $request);
        if ($expectedClass = self::determineExpectedClass($expectedClass, $request)) {
            $json = json_decode($body, true);
            return new $expectedClass($json);
        }
        return $response;
    }

    private static function getResponseErrors($body)
    {
        $json = json_decode($body, true);
        if (isset($json['error']['errors'])) {
            return $json['error']['errors'];
        }
        return null;
    }

    private static function decodeBody(ResponseInterface $response, RequestInterface $request = null)
    {
        if (self::isAltMedia($request)) {

            return '';
        }
        return (string)$response->getBody();
    }

    private static function isAltMedia(RequestInterface $request = null)
    {
        if ($request && $qs = $request->getUri()->getQuery()) {
            parse_str($qs, $query);
            if (isset($query['alt']) && $query['alt'] == 'media') {
                return true;
            }
        }
        return false;
    }

    private static function determineExpectedClass($expectedClass, RequestInterface $request = null)
    {
        if (false === $expectedClass) {
            return null;
        }
        if (is_null($request)) {
            return $expectedClass;
        }
        return $expectedClass ?: $request->getHeaderLine('X-Php-Expected-Class');
    }
}
