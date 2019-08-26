<?php

namespace Google\Auth;

interface FetchAuthTokenInterface
{
    public function fetchAuthToken(callable $httpHandler = null);

    public function getCacheKey();

    public function getLastReceivedToken();
}
