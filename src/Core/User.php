<?php

namespace Rokka\Client\Core;

/**
 * Represents a user
 */
class User
{
    /**
     * UUID v4
     *
     * @var string
     */
    public $id;

    /**
     * Email of user
     *
     * @var string
     */
    public $email;

    /**
     * Key
     *
     * @var string
     */
    public $apiKey;

    /**
     * Secret
     *
     * @var string
     */
    public $apiSecret;

    /**
     * Constructor
     *
     * @param string $id        Id
     * @param string $email     Email
     * @param string $apiKey    API key
     * @param string $apiSecret API secret
     */
    public function __construct($id, $email, $apiKey, $apiSecret)
    {
        $this->id = $id;
        $this->email = $email;
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Create a user from the JSON data returned by the rokka.io API.
     *
     * @param string $jsonString JSON as a string
     *
     * @return User
     */
    public static function createFromJsonResponse($jsonString)
    {
        $data = json_decode($jsonString, true);

        return new User($data['id'], $data['email'], $data['api_key'], $data['api_secret']);
    }

    /**
     * Get Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Get Api Key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Get Api Secret
     *
     * @return string
     */
    public function getApiSecret()
    {
        return $this->apiSecret;
    }
}
