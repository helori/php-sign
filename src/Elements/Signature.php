<?php

namespace Helori\PhpSign\Elements;

use Helori\PhpSign\Exceptions\ValidationException;


class Signature
{
    /**
     * The signer id
     *
     * @var int
     */
    protected $signerId;

    /**
     * The document id
     *
     * @var int
     */
    protected $documentId;

    /**
     * The label of the signature
     *
     * @var string
     */
    protected $label;

    /**
     * The page of the document on which the signature should appear
     *
     * @var int
     */
    protected $page;

    /**
     * The x coordinate of the signature rectangle
     *
     * @var int
     */
    protected $x;

    /**
     * The y coordinate of the signature rectangle
     *
     * @var int
     */
    protected $y;

    /**
     * The width of the signature rectangle
     *
     * @var int
     */
    protected $width;

    /**
     * The height of the signature rectangle
     *
     * @var int
     */
    protected $height;

    /**
     * Create a new Signature instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the signer id
     *
     * @return int
     */
    public function getSignerId()
    {
        return $this->signerId;
    }

    /**
     * Set the signer id
     *
     * @param  int  $signerId
     * @return int
     */
    public function setSignerId(int $signerId)
    {
        return $this->signerId = $signerId;
    }

    /**
     * Get the document id
     *
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Set the document id
     *
     * @param  int  $documentId
     * @return int
     */
    public function setDocumentId(int $documentId)
    {
        return $this->documentId = $documentId;
    }

    /**
     * Get the label of the signature
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the label of the signature
     *
     * @param  string  $label
     * @return string
     */
    public function setLabel(string $label)
    {
        return $this->label = $label;
    }

    /**
     * Get the page of the document on which the signature should appear
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the page of the document on which the signature should appear
     *
     * @param  int  $page
     * @return int
     */
    public function setPage(int $page)
    {
        return $this->page = $page;
    }

    /**
     * Get the x coordinate of the signature rectangle
     *
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Set the x coordinate of the signature rectangle
     *
     * @param  int  $x
     * @return int
     */
    public function setX(int $x)
    {
        return $this->x = $x;
    }

    /**
     * Get the y coordinate of the signature rectangle
     *
     * @return int
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Set the y coordinate of the signature rectangle
     *
     * @param  int  $y
     * @return int
     */
    public function setY(int $y)
    {
        return $this->y = $y;
    }

    /**
     * Get the width of the signature rectangle
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set the width of the signature rectangle
     *
     * @param  int  $width
     * @return int
     */
    public function setWidth(int $width)
    {
        return $this->width = $width;
    }

    /**
     * Get the height of the signature rectangle
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set the height of the signature rectangle
     *
     * @param  int  $height
     * @return int
     */
    public function setHeight(int $height)
    {
        return $this->height = $height;
    }

    /**
     * Get the coordinates of the signature rectangle
     *
     * @return array
     */
    public function getLocation()
    {
        return [
            $this->x,
            $this->y,
            $this->width,
            $this->height,
        ];
    }

    /**
     * Set the coordinates of the signature rectangle
     *
     * @param  array  $location
     * @return array
     */
    public function setLocation(array $location)
    {
        if(count($location) !== 4) {
            throw new ValidationException('The signature location must be an array containing 4 parameters : [x, y, width, height]');
        }

        $this->setX($location[0]);
        $this->setY($location[1]);
        $this->setWidth($location[2]);
        $this->setHeight($location[3]);

        return $location;
    }
}
