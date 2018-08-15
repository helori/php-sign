<?php

namespace Helori\PhpSign\Elements;


class Document
{
    /**
     * The document id
     *
     * @var integer
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
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the document's id
     *
     * @param  integer  $id
     * @return integer
     */
    public function setId(integer $id)
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
    public function setName($name)
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
    public function setFilepath($filepath)
    {
        return $this->filepath = $filepath;
    }
}
