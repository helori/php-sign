<?php

namespace Helori\PhpSign\Elements;


class DocumentResult extends Document
{
    /**
     * The url to download the file
     *
     * @var string
     */
    protected $url;

    /**
     * The file's content
     *
     * @var string
     */
    protected $content;

    /**
     * Create a new DocumentResult instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the document's url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the document's url
     *
     * @param  string  $url
     * @return string
     */
    public function setUrl(?string $url)
    {
        return $this->url = $url;
    }

    /**
     * Get the document's content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the document's content
     *
     * @param  string  $content
     * @return string
     */
    public function setContent(?string $content)
    {
        return $this->content = $content;
    }
}
