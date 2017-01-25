<?php

namespace Rokka\Client\Core;

/**
 * Represents a collection of source images
 */
class SourceImageCollection implements \Countable
{
    /**
     * Array of source images
     *
     * @var SourceImage[]
     */
    private $sourceImages = [];

    /**
     * Constructor
     *
     * @param SourceImage[] $sourceImages Array of source images
     */
    public function __construct(array $sourceImages)
    {
        $this->sourceImages = $sourceImages;
    }

    /**
     * Return number of source images
     *
     * @return integer
     */
    public function count()
    {
        return count($this->sourceImages);
    }

    /**
     * Return source images
     *
     * @return SourceImage[]
     */
    public function getSourceImages()
    {
        return $this->sourceImages;
    }

    /**
     * Create a collection from the JSON data returned by the rokka.io API.
     *
     * @param string $jsonString JSON as a string
     *
     * @return SourceImageCollection
     */
    public static function createFromJsonResponse($jsonString)
    {
        $data = json_decode($jsonString, true);

        $sourceImages = array_map(function ($sourceImage) {
            return SourceImage::createFromJsonResponse($sourceImage, true);
        }, $data['items']);

        return new SourceImageCollection($sourceImages);
    }
}

