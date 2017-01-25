<?php

namespace Rokka\Client\Core;

/**
 * Represents a single operation to be executed on an image.
 */
class Operation
{
    /**
     * Descriptive name of the operation.
     *
     * @var string
     */
    public $name;

    /**
     * Properties
     *
     * @var array
     */
    public $properties;

    /**
     * Required parameters
     *
     * @var array
     */
    public $required;

    /**
     * Constructor
     *
     * @param string $name       Name
     * @param array  $properties Properties
     * @param array  $required   Required parameters
     */
    public function __construct($name, array $properties, array $required)
    {
        $this->name = $name;
        $this->properties = $properties;
        $this->required = $required;
    }

    /**
     * Get Name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get Properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get Required properties
     *
     * @return array
     */
    public function getRequired()
    {
        return $this->required;
    }
}

