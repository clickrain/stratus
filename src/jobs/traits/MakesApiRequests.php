<?php

namespace clickrain\stratus\jobs\traits;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

/**
 *
 */
trait MakesApiRequests
{
    protected function makeRequest($path): ResponseInterface
    {
        $client = new Client();

        $url = rtrim($this->_settings->getBaseUrl(), '/') . $path;

        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->_settings->getApiKey(),
                'Accept' => 'application/json',
                'X-Version' => 'v1.1',
            ],
        ];

        return $client->get($url, $options);
    }
}
