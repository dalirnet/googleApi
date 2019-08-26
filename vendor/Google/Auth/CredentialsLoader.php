<?php

namespace Google\Auth;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Credentials\UserRefreshCredentials;
use GuzzleHttp\Psr7;
use Psr\HttpMessage\StreamInterface;

abstract class CredentialsLoader implements FetchAuthTokenInterface
{
    const TOKEN_CREDENTIAL_URI = 'https://www.googleapis.com/oauth2/v4/token';
    const ENV_VAR = 'GOOGLE_APPLICATION_CREDENTIALS';
    const WELL_KNOWN_PATH = 'gcloud/application_default_credentials.json';
    const NON_WINDOWS_WELL_KNOWN_PATH_BASE = '.config';
    const AUTH_METADATA_KEY = 'Authorization';

    public static function fromEnv($scope = null)
    {
        $path = getenv(self::ENV_VAR);
        if (empty($path)) {
            return;
        }
        if (!file_exists($path)) {
            $cause = 'file ' . $path . ' does not exist';
            throw new \DomainException(self::unableToReadEnv($cause));
        }
        $keyStream = Psr7\stream_for(file_get_contents($path));
        return static::makeCredentials($scope, $keyStream);
    }

    private static function unableToReadEnv($cause)
    {
        $msg = 'Unable to read the credential file specified by ';
        $msg .= ' GOOGLE_APPLICATION_CREDENTIALS: ';
        $msg .= $cause;
        return $msg;
    }

    public static function makeCredentials($scope, StreamInterface $jsonKeyStream)
    {
        $jsonKey = json_decode($jsonKeyStream->getContents(), true);
        if (!array_key_exists('type', $jsonKey)) {
            throw new \InvalidArgumentException('json key is missing the type field');
        }
        if ($jsonKey['type'] == 'service_account') {
            return new ServiceAccountCredentials($scope, $jsonKey);
        } elseif ($jsonKey['type'] == 'authorized_user') {
            return new UserRefreshCredentials($scope, $jsonKey);
        } else {
            throw new \InvalidArgumentException('invalid value in the type field');
        }
    }

    public static function fromWellKnownFile($scope = null)
    {
        $rootEnv = self::isOnWindows() ? 'APPDATA' : 'HOME';
        $path = [getenv($rootEnv)];
        if (!self::isOnWindows()) {
            $path[] = self::NON_WINDOWS_WELL_KNOWN_PATH_BASE;
        }
        $path[] = self::WELL_KNOWN_PATH;
        $path = implode(DIRECTORY_SEPARATOR, $path);
        if (!file_exists($path)) {
            return;
        }
        $keyStream = Psr7\stream_for(file_get_contents($path));
        return static::makeCredentials($scope, $keyStream);
    }

    private static function isOnWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public function getUpdateMetadataFunc()
    {
        return array($this, 'updateMetadata');
    }

    public function updateMetadata($metadata, $authUri = null, callable $httpHandler = null)
    {
        $result = $this->fetchAuthToken($httpHandler);
        if (!isset($result['access_token'])) {
            return $metadata;
        }
        $metadata_copy = $metadata;
        $metadata_copy[self::AUTH_METADATA_KEY] = array('Bearer ' . $result['access_token']);
        return $metadata_copy;
    }
}
