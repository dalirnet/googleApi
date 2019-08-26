<?php

namespace Guzzle\Http\Handler;

use Guzzle\Http\Exception\ConnectException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\TransferStats;
use Guzzle\Promise\FulfilledPromise;
use Guzzle\Promise\RejectedPromise;
use Guzzle\Psr7;
use Guzzle\Psr7\LazyOpenStream;
use Psr\HttpMessage\RequestInterface;

class CurlFactory implements CurlFactoryInterface
{
    private $handles = [];
    private $maxHandles;

    public function __construct($maxHandles)
    {
        $this->maxHandles = $maxHandles;
    }

    public static function finish(callable $handler, EasyHandle $easy, CurlFactoryInterface $factory)
    {
        if (isset($easy->options['on_stats'])) {
            self::invokeStats($easy);
        }
        if (!$easy->response || $easy->errno) {
            return self::finishError($handler, $easy, $factory);
        }
        $factory->release($easy);
        $body = $easy->response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        return new FulfilledPromise($easy->response);
    }

    private static function invokeStats(EasyHandle $easy)
    {
        $curlStats = curl_getinfo($easy->handle);
        $stats = new TransferStats($easy->request, $easy->response, $curlStats['total_time'], $easy->errno, $curlStats);
        call_user_func($easy->options['on_stats'], $stats);
    }

    private static function finishError(callable $handler, EasyHandle $easy, CurlFactoryInterface $factory)
    {

        $ctx = ['errno' => $easy->errno, 'error' => curl_error($easy->handle),] + curl_getinfo($easy->handle);
        $factory->release($easy);
        if (empty($easy->options['_err_message']) && (!$easy->errno || $easy->errno == 65)) {
            return self::retryFailedRewind($handler, $easy, $ctx);
        }
        return self::createRejection($easy, $ctx);
    }

    private static function retryFailedRewind(callable $handler, EasyHandle $easy, array $ctx)
    {
        try {

            $body = $easy->request->getBody();
            if ($body->tell() > 0) {
                $body->rewind();
            }
        } catch (\RuntimeException $e) {
            $ctx['error'] = 'The connection unexpectedly failed without ' . 'providing an error. The request would have been retried, ' . 'but attempting to rewind the request body failed. ' . 'Exception: ' . $e;
            return self::createRejection($easy, $ctx);
        }
        if (!isset($easy->options['_curl_retries'])) {
            $easy->options['_curl_retries'] = 1;
        } elseif ($easy->options['_curl_retries'] == 2) {
            $ctx['error'] = 'The cURL request was retried 3 times ' . 'and did not succeed. The most likely reason for the failure ' . 'is that cURL was unable to rewind the body of the request ' . 'and subsequent retries resulted in the same error. Turn on ' . 'the debug option to see what went wrong. See ' . 'https://bugs.php.net/bug.php?id=47204 for more information.';
            return self::createRejection($easy, $ctx);
        } else {
            $easy->options['_curl_retries']++;
        }
        return $handler($easy->request, $easy->options);
    }

    private static function createRejection(EasyHandle $easy, array $ctx)
    {
        static $connectionErrors = [CURLE_OPERATION_TIMEOUTED => true, CURLE_COULDNT_RESOLVE_HOST => true, CURLE_COULDNT_CONNECT => true, CURLE_SSL_CONNECT_ERROR => true, CURLE_GOT_NOTHING => true,];
        if ($easy->onHeadersException) {
            return new RejectedPromise(new RequestException('An error was encountered during the on_headers event', $easy->request, $easy->response, $easy->onHeadersException, $ctx));
        }
        $message = sprintf('cURL error %s: %s (%s)', $ctx['errno'], $ctx['error'], 'see http://curl.haxx.se/libcurl/c/libcurl-errors.html');
        $error = isset($connectionErrors[$easy->errno]) ? new ConnectException($message, $easy->request, null, $ctx) : new RequestException($message, $easy->request, $easy->response, null, $ctx);
        return new RejectedPromise($error);
    }

    public function create(RequestInterface $request, array $options)
    {
        if (isset($options['curl']['body_as_string'])) {
            $options['_body_as_string'] = $options['curl']['body_as_string'];
            unset($options['curl']['body_as_string']);
        }
        $easy = new EasyHandle;
        $easy->request = $request;
        $easy->options = $options;
        $conf = $this->getDefaultConf($easy);
        $this->applyMethod($easy, $conf);
        $this->applyHandlerOptions($easy, $conf);
        $this->applyHeaders($easy, $conf);
        unset($conf['_headers']);
        if (isset($options['curl'])) {
            $conf = array_replace($conf, $options['curl']);
        }
        $conf[CURLOPT_HEADERFUNCTION] = $this->createHeaderFn($easy);
        $easy->handle = $this->handles ? array_pop($this->handles) : curl_init();
        curl_setopt_array($easy->handle, $conf);
        return $easy;
    }

    private function getDefaultConf(EasyHandle $easy)
    {
        $conf = ['_headers' => $easy->request->getHeaders(), CURLOPT_CUSTOMREQUEST => $easy->request->getMethod(), CURLOPT_URL => (string)$easy->request->getUri(), CURLOPT_RETURNTRANSFER => false, CURLOPT_HEADER => false, CURLOPT_CONNECTTIMEOUT => 150,];
        if (defined('CURLOPT_PROTOCOLS')) {
            $conf[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        $version = $easy->request->getProtocolVersion();
        if ($version == 1.1) {
            $conf[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        } elseif ($version == 2.0) {
            $conf[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        } else {
            $conf[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
        }
        return $conf;
    }

    private function applyMethod(EasyHandle $easy, array &$conf)
    {
        $body = $easy->request->getBody();
        $size = $body->getSize();
        if ($size === null || $size > 0) {
            $this->applyBody($easy->request, $easy->options, $conf);
            return;
        }
        $method = $easy->request->getMethod();
        if ($method === 'PUT' || $method === 'POST') {

            if (!$easy->request->hasHeader('Content-Length')) {
                $conf[CURLOPT_HTTPHEADER][] = 'Content-Length: 0';
            }
        } elseif ($method === 'HEAD') {
            $conf[CURLOPT_NOBODY] = true;
            unset($conf[CURLOPT_WRITEFUNCTION], $conf[CURLOPT_READFUNCTION], $conf[CURLOPT_FILE], $conf[CURLOPT_INFILE]);
        }
    }

    private function applyBody(RequestInterface $request, array $options, array &$conf)
    {
        $size = $request->hasHeader('Content-Length') ? (int)$request->getHeaderLine('Content-Length') : null;
        if (($size !== null && $size < 1000000) || !empty($options['_body_as_string'])) {
            $conf[CURLOPT_POSTFIELDS] = (string)$request->getBody();
            $this->removeHeader('Content-Length', $conf);
            $this->removeHeader('Transfer-Encoding', $conf);
        } else {
            $conf[CURLOPT_UPLOAD] = true;
            if ($size !== null) {
                $conf[CURLOPT_INFILESIZE] = $size;
                $this->removeHeader('Content-Length', $conf);
            }
            $body = $request->getBody();
            if ($body->isSeekable()) {
                $body->rewind();
            }
            $conf[CURLOPT_READFUNCTION] = function ($ch, $fd, $length) use ($body) {
                return $body->read($length);
            };
        }
        if (!$request->hasHeader('Expect')) {
            $conf[CURLOPT_HTTPHEADER][] = 'Expect:';
        }
        if (!$request->hasHeader('Content-Type')) {
            $conf[CURLOPT_HTTPHEADER][] = 'Content-Type:';
        }
    }

    private function removeHeader($name, array &$options)
    {
        foreach (array_keys($options['_headers']) as $key) {
            if (!strcasecmp($key, $name)) {
                unset($options['_headers'][$key]);
                return;
            }
        }
    }

    private function applyHandlerOptions(EasyHandle $easy, array &$conf)
    {
        $options = $easy->options;
        if (isset($options['verify'])) {
            if ($options['verify'] === false) {
                unset($conf[CURLOPT_CAINFO]);
                $conf[CURLOPT_SSL_VERIFYHOST] = 0;
                $conf[CURLOPT_SSL_VERIFYPEER] = false;
            } else {
                $conf[CURLOPT_SSL_VERIFYHOST] = 2;
                $conf[CURLOPT_SSL_VERIFYPEER] = true;
                if (is_string($options['verify'])) {
                    $conf[CURLOPT_CAINFO] = $options['verify'];
                    if (!file_exists($options['verify'])) {
                        throw new \InvalidArgumentException("SSL CA bundle not found: {$options['verify']}");
                    }
                }
            }
        }
        if (!empty($options['decode_content'])) {
            $accept = $easy->request->getHeaderLine('Accept-Encoding');
            if ($accept) {
                $conf[CURLOPT_ENCODING] = $accept;
            } else {
                $conf[CURLOPT_ENCODING] = '';
                $conf[CURLOPT_HTTPHEADER][] = 'Accept-Encoding:';
            }
        }
        if (isset($options['sink'])) {
            $sink = $options['sink'];
            if (!is_string($sink)) {
                $sink = \Guzzle\Psr7\stream_for($sink);
            } elseif (!is_dir(dirname($sink))) {

                throw new \RuntimeException(sprintf('Directory %s does not exist for sink value of %s', dirname($sink), $sink));
            } else {
                $sink = new LazyOpenStream($sink, 'w+');
            }
            $easy->sink = $sink;
            $conf[CURLOPT_WRITEFUNCTION] = function ($ch, $write) use ($sink) {
                return $sink->write($write);
            };
        } else {

            $conf[CURLOPT_FILE] = fopen('php://temp', 'w+');
            $easy->sink = Psr7\stream_for($conf[CURLOPT_FILE]);
        }
        if (isset($options['timeout'])) {
            $conf[CURLOPT_TIMEOUT_MS] = $options['timeout'] * 1000;
        }
        if (isset($options['connect_timeout'])) {
            $conf[CURLOPT_CONNECTTIMEOUT_MS] = $options['connect_timeout'] * 1000;
        }
        if (isset($options['proxy'])) {
            if (!is_array($options['proxy'])) {
                $conf[CURLOPT_PROXY] = $options['proxy'];
            } else {
                $scheme = $easy->request->getUri()->getScheme();
                if (isset($options['proxy'][$scheme])) {
                    $host = $easy->request->getUri()->getHost();
                    if (!isset($options['proxy']['no']) || !\Guzzle\Http\is_host_in_noproxy($host, $options['proxy']['no'])) {
                        $conf[CURLOPT_PROXY] = $options['proxy'][$scheme];
                    }
                }
            }
        }
        if (isset($options['cert'])) {
            $cert = $options['cert'];
            if (is_array($cert)) {
                $conf[CURLOPT_SSLCERTPASSWD] = $cert[1];
                $cert = $cert[0];
            }
            if (!file_exists($cert)) {
                throw new \InvalidArgumentException("SSL certificate not found: {$cert}");
            }
            $conf[CURLOPT_SSLCERT] = $cert;
        }
        if (isset($options['ssl_key'])) {
            $sslKey = $options['ssl_key'];
            if (is_array($sslKey)) {
                $conf[CURLOPT_SSLKEYPASSWD] = $sslKey[1];
                $sslKey = $sslKey[0];
            }
            if (!file_exists($sslKey)) {
                throw new \InvalidArgumentException("SSL private key not found: {$sslKey}");
            }
            $conf[CURLOPT_SSLKEY] = $sslKey;
        }
        if (isset($options['progress'])) {
            $progress = $options['progress'];
            if (!is_callable($progress)) {
                throw new \InvalidArgumentException('progress client option must be callable');
            }
            $conf[CURLOPT_NOPROGRESS] = false;
            $conf[CURLOPT_PROGRESSFUNCTION] = function () use ($progress) {
                $args = func_get_args();
                if (is_resource($args[0])) {
                    array_shift($args);
                }
                call_user_func_array($progress, $args);
            };
        }
        if (!empty($options['debug'])) {
            $conf[CURLOPT_STDERR] = \Guzzle\Http\debug_resource($options['debug']);
            $conf[CURLOPT_VERBOSE] = true;
        }
    }

    private function applyHeaders(EasyHandle $easy, array &$conf)
    {
        foreach ($conf['_headers'] as $name => $values) {
            foreach ($values as $value) {
                $conf[CURLOPT_HTTPHEADER][] = "$name: $value";
            }
        }
        if (!$easy->request->hasHeader('Accept')) {
            $conf[CURLOPT_HTTPHEADER][] = 'Accept:';
        }
    }

    private function createHeaderFn(EasyHandle $easy)
    {
        if (!isset($easy->options['on_headers'])) {
            $onHeaders = null;
        } elseif (!is_callable($easy->options['on_headers'])) {
            throw new \InvalidArgumentException('on_headers must be callable');
        } else {
            $onHeaders = $easy->options['on_headers'];
        }
        return function ($ch, $h) use (
            $onHeaders, $easy, &$startingResponse
        ) {
            $value = trim($h);
            if ($value === '') {
                $startingResponse = true;
                $easy->createResponse();
                if ($onHeaders) {
                    try {
                        $onHeaders($easy->response);
                    } catch (\Exception $e) {

                        $easy->onHeadersException = $e;
                        return -1;
                    }
                }
            } elseif ($startingResponse) {
                $startingResponse = false;
                $easy->headers = [$value];
            } else {
                $easy->headers[] = $value;
            }
            return strlen($h);
        };
    }

    public function release(EasyHandle $easy)
    {
        $resource = $easy->handle;
        unset($easy->handle);
        if (count($this->handles) >= $this->maxHandles) {
            curl_close($resource);
        } else {

            curl_setopt($resource, CURLOPT_HEADERFUNCTION, null);
            curl_setopt($resource, CURLOPT_READFUNCTION, null);
            curl_setopt($resource, CURLOPT_WRITEFUNCTION, null);
            curl_setopt($resource, CURLOPT_PROGRESSFUNCTION, null);
            if (substr(phpversion(), 0, 3) != "5.4") {
                curl_reset($resource);
            }
            $this->handles[] = $resource;
        }
    }
}
