<?php

namespace Rokka\Client;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Base Client class
 */
abstract class Base
{
    const DEFAULT_API_BASE_URL = 'https://api.rokka.io';
    const DEFAULT_API_VERSION = 1;

    const API_KEY_HEADER = 'Api-Key';
    const API_VERSION_HEADER = 'Api-Version';
    const API_SIGNATURE_HEADER = 'Api-Signature';

    /**
     * @var integer
     */
    private $apiVersion = self::DEFAULT_API_VERSION;

    /**
     * Client to access Rokka
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * Rokka credentials.
     *
     * @var array
     */
    private $credentials = [
        'key'       => '',
        'secret' => '',
    ];

    /**
     * Constructor.
     *
     * @param ClientInterface $client Client instance
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Set the credentials.
     *
     * @param string $key    API key
     * @param string $secret API secret
     *
     * @return void
     */
    public function setCredentials($key, $secret)
    {
        $this->credentials = ['key' => $key, 'secret' => $secret];
    }

    /**
     * Call the API rokka endpoint.
     *
     * @param string  $method           HTTP method to use
     * @param string  $path             Path on the API
     * @param array   $options          Request options
     * @param boolean $needsCredentials True if credentials are needed
     *
     * @return Response
     */
    protected function call($method, $path, array $options = [], $needsCredentials = true)
    {
        $options['headers'][self::API_VERSION_HEADER] = $this->apiVersion;

        if ($needsCredentials) {
            $url = (string) $this->client->getConfig('base_uri');
            $dataToHash = $this->credentials['secret'] . $url . '/' . $path;

            if (isset($options['query'])) {
                $dataToHash .= '?' . http_build_query($options['query']);
            }

            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $dataToHash .= sha1($this->getEncodedBodyData($options));
            }

            $options['headers'][self::API_SIGNATURE_HEADER] = sha1($dataToHash);
            $options['headers'][self::API_KEY_HEADER]       = $this->credentials['key'];
        }

        return $this->client->request($method, $path, $options);
    }

    /**
     * Return JSON encoded body data for creating sha1 strings.
     *
     * @param array $options Options
     *
     * @return string
     */
    private function getEncodedBodyData(array $options)
    {
        if (isset($options['json'])) {
            return json_encode($options['json']);
        }

        if (isset($options['multipart'])) {
            return json_encode($options['multipart']);
        }

        return '';
    }
}
