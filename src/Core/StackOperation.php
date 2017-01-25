<?php

namespace Rokka\Client\Core;

/**
 * Represents an operation with configuration
 */
class StackOperation
{
    /**
     * Name of the operation
     *
     * @var string
     */
    public $name;

    /**
     * Configured options provided for the stack
     *
     * @var array
     */
    public $options = array();

    /**
     * Constructor.
     *
     * @param string $name    Operation name
     * @param array  $options Optional options for the operation
     */
    public function __construct($name, array $options = array())
    {
        $this->name = $name;
        $this->options   = $options;
    }

    /**
     * Return the stack operation as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name'    => $this->name,
            'options' => $this->options,
        ];
    }

    /**
     * Create a stack operation from the JSON data returned by the rokka.io API.
     *
     * @param string|array $data    JSON data
     * @param boolean      $isArray If the data provided is already an array
     *
     * @return StackOperation
     */
    public static function createFromJsonResponse($data, $isArray = false)
    {
        if (!$isArray) {
            $data = json_decode($data, true);
        }

        return new StackOperation($data['name'], $data['options']);
    }
}
