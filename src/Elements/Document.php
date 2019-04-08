<?php

namespace Helori\PhpSign\Elements;


class Document
{
    /**
     * The document id
     *
     * @var int
     */
    protected $id;

    /**
     * The document name
     *
     * @var string
     */
    protected $name;

    /**
     * The document absolute path
     *
     * @var string
     */
    protected $filepath;

    /**
     * The document metadata
     *
     * @var array
     */
    protected $metadata = [];

    /**
     * Create a new Document instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the document's id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the document's id
     *
     * @param  int  $id
     * @return int
     */
    public function setId(int $id)
    {
        return $this->id = $id;
    }

    /**
     * Get the document's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the document's name
     *
     * @param  string  $name
     * @return string
     */
    public function setName(?string $name)
    {
        return $this->name = $name;
    }

    /**
     * Get the document's file absolute path
     *
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * Set the document's file absolute path
     *
     * @param  string  $filepath
     * @return string
     */
    public function setFilepath(?string $filepath)
    {
        return $this->filepath = $filepath;
    }

    /**
     * Get the document's metadata
     *
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set the document's metadata
     *
     * @param  array  $metadata
     * @return array
     */
    public function setMetadata(array $metadata)
    {
        return $this->metadata = $metadata;
    }
}
