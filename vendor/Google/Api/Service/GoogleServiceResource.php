<?php

namespace Google\Api\Service;

use Google\Api\GoogleException;
use Google\Api\GoogleModel;
use Google\Api\Http\GoogleHttpMediaFileUpload;
use Google\Api\Utils\GoogleUtilsUriTemplate;
use Guzzle\Psr7\Request;

class GoogleServiceResource
{
    private $stackParameters = array('alt' => array('type' => 'string', 'location' => 'query'), 'fields' => array('type' => 'string', 'location' => 'query'), 'trace' => array('type' => 'string', 'location' => 'query'), 'userIp' => array('type' => 'string', 'location' => 'query'), 'quotaUser' => array('type' => 'string', 'location' => 'query'), 'data' => array('type' => 'string', 'location' => 'body'), 'mimeType' => array('type' => 'string', 'location' => 'header'), 'uploadType' => array('type' => 'string', 'location' => 'query'), 'mediaUpload' => array('type' => 'complex', 'location' => 'query'), 'prettyPrint' => array('type' => 'string', 'location' => 'query'),);
    private $rootUrl;
    private $client;
    private $serviceName;
    private $servicePath;
    private $resourceName;
    private $methods;

    public function __construct($service, $serviceName, $resourceName, $resource)
    {
        $this->rootUrl = $service->rootUrl;
        $this->client = $service->getClient();
        $this->servicePath = $service->servicePath;
        $this->serviceName = $serviceName;
        $this->resourceName = $resourceName;
        $this->methods = is_array($resource) && isset($resource['methods']) ? $resource['methods'] : array($resourceName => $resource);
    }

    public function call($name, $arguments, $expectedClass = null)
    {
        if (!isset($this->methods[$name])) {
            $this->client->getLogger()->error('Service method unknown', array('service' => $this->serviceName, 'resource' => $this->resourceName, 'method' => $name));
            throw new GoogleException("Unknown function: " . "{$this->serviceName}->{$this->resourceName}->{$name}()");
        }
        $method = $this->methods[$name];
        $parameters = $arguments[0];
        $postBody = null;
        if (isset($parameters['postBody'])) {
            if ($parameters['postBody'] instanceof GoogleModel) {

                $parameters['postBody'] = $parameters['postBody']->toSimpleObject();
            } else if (is_object($parameters['postBody'])) {

                $parameters['postBody'] = $this->convertToArrayAndStripNulls($parameters['postBody']);
            }
            $postBody = (array)$parameters['postBody'];
            unset($parameters['postBody']);
        }
        if (isset($parameters['optParams'])) {
            $optParams = $parameters['optParams'];
            unset($parameters['optParams']);
            $parameters = array_merge($parameters, $optParams);
        }
        if (!isset($method['parameters'])) {
            $method['parameters'] = array();
        }
        $method['parameters'] = array_merge($this->stackParameters, $method['parameters']);
        foreach ($parameters as $key => $val) {
            if ($key != 'postBody' && !isset($method['parameters'][$key])) {
                $this->client->getLogger()->error('Service parameter unknown', array('service' => $this->serviceName, 'resource' => $this->resourceName, 'method' => $name, 'parameter' => $key));
                throw new GoogleException("($name) unknown parameter: '$key'");
            }
        }
        foreach ($method['parameters'] as $paramName => $paramSpec) {
            if (isset($paramSpec['required']) && $paramSpec['required'] && !isset($parameters[$paramName])) {
                $this->client->getLogger()->error('Service parameter missing', array('service' => $this->serviceName, 'resource' => $this->resourceName, 'method' => $name, 'parameter' => $paramName));
                throw new GoogleException("($name) missing required param: '$paramName'");
            }
            if (isset($parameters[$paramName])) {
                $value = $parameters[$paramName];
                $parameters[$paramName] = $paramSpec;
                $parameters[$paramName]['value'] = $value;
                unset($parameters[$paramName]['required']);
            } else {

                unset($parameters[$paramName]);
            }
        }
        $this->client->getLogger()->info('Service Call', array('service' => $this->serviceName, 'resource' => $this->resourceName, 'method' => $name, 'arguments' => $parameters,));
        $url = $this->createRequestUri($method['path'], $parameters);
        $request = new Request($method['httpMethod'], $url, ['content-type' => 'application/json'], $postBody ? json_encode($postBody) : '');
        if (isset($parameters['data'])) {
            $mimeType = isset($parameters['mimeType']) ? $parameters['mimeType']['value'] : 'application/octet-stream';
            $data = $parameters['data']['value'];
            $upload = new GoogleHttpMediaFileUpload($this->client, $request, $mimeType, $data);
            $request = $upload->getRequest();
        }
        if (isset($parameters['alt']) && $parameters['alt']['value'] == 'media') {
            $expectedClass = null;
        }
        if ($this->client->shouldDefer()) {

            $request = $request->withHeader('X-Php-Expected-Class', $expectedClass);
            return $request;
        }
        return $this->client->execute($request, $expectedClass);
    }

    protected function convertToArrayAndStripNulls($o)
    {
        $o = (array)$o;
        foreach ($o as $k => $v) {
            if ($v === null) {
                unset($o[$k]);
            } elseif (is_object($v) || is_array($v)) {
                $o[$k] = $this->convertToArrayAndStripNulls($o[$k]);
            }
        }
        return $o;
    }

    public function createRequestUri($restPath, $params)
    {
        $requestUrl = $this->servicePath . $restPath;
        if ($this->rootUrl) {
            if ('/' !== substr($this->rootUrl, -1) && '/' !== substr($requestUrl, 0, 1)) {
                $requestUrl = '/' . $requestUrl;
            }
            $requestUrl = $this->rootUrl . $requestUrl;
        }
        $uriTemplateVars = array();
        $queryVars = array();
        foreach ($params as $paramName => $paramSpec) {
            if ($paramSpec['type'] == 'boolean') {
                $paramSpec['value'] = ($paramSpec['value']) ? 'true' : 'false';
            }
            if ($paramSpec['location'] == 'path') {
                $uriTemplateVars[$paramName] = $paramSpec['value'];
            } else if ($paramSpec['location'] == 'query') {
                if (isset($paramSpec['repeated']) && is_array($paramSpec['value'])) {
                    foreach ($paramSpec['value'] as $value) {
                        $queryVars[] = $paramName . '=' . rawurlencode(rawurldecode($value));
                    }
                } else {
                    $queryVars[] = $paramName . '=' . rawurlencode(rawurldecode($paramSpec['value']));
                }
            }
        }
        if (count($uriTemplateVars)) {
            $uriTemplateParser = new GoogleUtilsUriTemplate();
            $requestUrl = $uriTemplateParser->parse($requestUrl, $uriTemplateVars);
        }
        if (count($queryVars)) {
            $requestUrl .= '?' . implode($queryVars, '&');
        }
        return $requestUrl;
    }
}
