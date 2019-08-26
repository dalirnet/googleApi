<?php

namespace Google\Api\Http;

use Guzzle\Psr7;
use Guzzle\Psr7\Request;
use Guzzle\Psr7\Uri;
use Psr\HttpMessage\RequestInterface;

class GoogleHttpMediaFileUpload
{
    const UPLOAD_MEDIA_TYPE = 'media';
    const UPLOAD_MULTIPART_TYPE = 'multipart';
    const UPLOAD_RESUMABLE_TYPE = 'resumable';
    private $mimeType;
    private $data;
    private $resumable;
    private $chunkSize;
    private $size;
    private $resumeUri;
    private $progress;
    private $client;
    private $request;
    private $boundary;
    private $httpResultCode;

    public function __construct(Google_Client $client, RequestInterface $request, $mimeType, $data, $resumable = false, $chunkSize = false)
    {
        $this->client = $client;
        $this->request = $request;
        $this->mimeType = $mimeType;
        $this->data = $data;
        $this->resumable = $resumable;
        $this->chunkSize = $chunkSize;
        $this->progress = 0;
        $this->process();
    }

    private function process()
    {
        $this->transformToUploadUrl();
        $request = $this->request;
        $postBody = '';
        $contentType = false;
        $meta = (string)$request->getBody();
        $meta = is_string($meta) ? json_decode($meta, true) : $meta;
        $uploadType = $this->getUploadType($meta);
        $request = $request->withUri(Uri::withQueryValue($request->getUri(), 'uploadType', $uploadType));
        $mimeType = $this->mimeType ? $this->mimeType : $request->getHeaderLine('content-type');
        if (self::UPLOAD_RESUMABLE_TYPE == $uploadType) {
            $contentType = $mimeType;
            $postBody = is_string($meta) ? $meta : json_encode($meta);
        } else if (self::UPLOAD_MEDIA_TYPE == $uploadType) {
            $contentType = $mimeType;
            $postBody = $this->data;
        } else if (self::UPLOAD_MULTIPART_TYPE == $uploadType) {

            $boundary = $this->boundary ? $this->boundary : mt_rand();
            $boundary = str_replace('"', '', $boundary);
            $contentType = 'multipart/related; boundary=' . $boundary;
            $related = "--$boundary\r\n";
            $related .= "Content-Type: application/json; charset=UTF-8\r\n";
            $related .= "\r\n" . json_encode($meta) . "\r\n";
            $related .= "--$boundary\r\n";
            $related .= "Content-Type: $mimeType\r\n";
            $related .= "Content-Transfer-Encoding: base64\r\n";
            $related .= "\r\n" . base64_encode($this->data) . "\r\n";
            $related .= "--$boundary--";
            $postBody = $related;
        }
        $request = $request->withBody(Psr7\stream_for($postBody));
        if (isset($contentType) && $contentType) {
            $request = $request->withHeader('content-type', $contentType);
        }
        return $this->request = $request;
    }

    private function transformToUploadUrl()
    {
        $parts = parse_url((string)$this->request->getUri());
        if (!isset($parts['path'])) {
            $parts['path'] = '';
        }
        $parts['path'] = '/upload' . $parts['path'];
        $uri = Uri::fromParts($parts);
        $this->request = $this->request->withUri($uri);
    }

    public function getUploadType($meta)
    {
        if ($this->resumable) {
            return self::UPLOAD_RESUMABLE_TYPE;
        }
        if (false == $meta && $this->data) {
            return self::UPLOAD_MEDIA_TYPE;
        }
        return self::UPLOAD_MULTIPART_TYPE;
    }

    public function setFileSize($size)
    {
        $this->size = $size;
    }

    public function getProgress()
    {
        return $this->progress;
    }

    public function nextChunk($chunk = false)
    {
        $resumeUri = $this->getResumeUri();
        if (false == $chunk) {
            $chunk = substr($this->data, $this->progress, $this->chunkSize);
        }
        $lastBytePos = $this->progress + strlen($chunk) - 1;
        $headers = array('content-range' => "bytes $this->progress-$lastBytePos/$this->size", 'content-length' => strlen($chunk), 'expect' => '',);
        $request = new Request('PUT', $resumeUri, $headers, Psr7\stream_for($chunk));
        return $this->makePutRequest($request);
    }

    public function getResumeUri()
    {
        if (is_null($this->resumeUri)) {
            $this->resumeUri = $this->fetchResumeUri();
        }
        return $this->resumeUri;
    }

    private function fetchResumeUri()
    {
        $result = null;
        $body = $this->request->getBody();
        if ($body) {
            $headers = array('content-type' => 'application/json; charset=UTF-8', 'content-length' => $body->getSize(), 'x-upload-content-type' => $this->mimeType, 'x-upload-content-length' => $this->size, 'expect' => '',);
            foreach ($headers as $key => $value) {
                $this->request = $this->request->withHeader($key, $value);
            }
        }
        $response = $this->client->execute($this->request, false);
        $location = $response->getHeaderLine('location');
        $code = $response->getStatusCode();
        if (200 == $code && true == $location) {
            return $location;
        }
        $message = $code;
        $body = json_decode((string)$this->request->getBody(), true);
        if (isset($body['error']['errors'])) {
            $message .= ': ';
            foreach ($body['error']['errors'] as $error) {
                $message .= "{$error[domain]}, {$error[message]};";
            }
            $message = rtrim($message, ';');
        }
        $error = "Failed to start the resumable upload (HTTP {$message})";
        $this->client->getLogger()->error($error);
        throw new Google_Exception($error);
    }

    private function makePutRequest(RequestInterface $request)
    {
        $response = $this->client->execute($request);
        $this->httpResultCode = $response->getStatusCode();
        if (308 == $this->httpResultCode) {

            $range = explode('-', $response->getHeaderLine('range'));
            $this->progress = $range[1] + 1;
            $location = $response->getHeaderLine('location');
            if ($location) {
                $this->resumeUri = $location;
            }
            return false;
        }
        return GoogleHttpREST::decodeHttpResponse($response, $this->request);
    }

    public function getHttpResultCode()
    {
        return $this->httpResultCode;
    }

    public function resume($resumeUri)
    {
        $this->resumeUri = $resumeUri;
        $headers = array('content-range' => "bytes */$this->size", 'content-length' => 0,);
        $httpRequest = new Request('PUT', $this->resumeUri, $headers);
        return $this->makePutRequest($httpRequest);
    }

    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;
    }

    public function getRequest()
    {
        return $this->request;
    }
}
