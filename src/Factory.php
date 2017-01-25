<?php

namespace Rokka\Client;

use GuzzleHttp\Client as GuzzleClient;
use Rokka\Client\Base as BaseClient;
use Rokka\Client\Image as ImageClient;
use Rokka\Client\User as UserClient;

/**
 * Factory class with static methods to easily instantiate clients.
 */
class Factory
{
    /**
     * Return an image client
     *
     * @param string $organization Organization name
     * @param string $apiKey       API key
     * @param string $apiSecret    API secret
     * @param string $baseUrl      Optional base url
     *
     * @return Image
     */
    public static function getImageClient($organization, $apiKey, $apiSecret, $baseUrl = BaseClient::DEFAULT_API_BASE_URL)
    {
        $client = new GuzzleClient([
            'base_uri' => $baseUrl
        ]);

        return new ImageClient($client, $organization, $apiKey, $apiSecret);
    }

    /**
     * Return a user client
     *
     * @param string $baseUrl Optional base url
     *
     * @return UserClient
     */
    public static function getUserClient($baseUrl = BaseClient::DEFAULT_API_BASE_URL)
    {
        $client = new GuzzleClient([
            'base_uri' => $baseUrl
        ]);

        return new UserClient($client);
    }
}
