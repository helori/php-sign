<?php

namespace Helori\PhpSign\Elements;

use Helori\PhpSign\Exceptions\ValidationException;


class Signature
{
    /**
     * The signer id
     *
     * @var integer
     */
    protected $signerId;

    /**
     * The document id
     *
     * @var integer
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
     * @var integer
     */
    protected $page;

    /**
     * The x coordinate of the signature rectangle
     *
     * @var integer
     */
    protected $x;

    /**
     * The y coordinate of the signature rectangle
     *
     * @var integer
     */
    protected $y;

    /**
     * The width of the signature rectangle
     *
     * @var integer
     */
    protected $width;

    /**
     * The height of the signature rectangle
     *
     * @var integer
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
     * @return integer
     */
    public function getSignerId()
    {
        return $this->signerId;
    }

    /**
     * Set the signer id
     *
     * @param  integer  $signerId
     * @return integer
     */
    public function setSignerId(integer $signerId)
    {
        return $this->signerId = $signerId;
    }

    /**
     * Get the document id
     *
     * @return integer
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * Set the document id
     *
     * @param  integer  $documentId
     * @return integer
     */
    public function setDocumentId(integer $documentId)
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
    public function setLabel(integer $label)
    {
        return $this->label = $label;
    }

    /**
     * Get the page of the document on which the signature should appear
     *
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the page of the document on which the signature should appear
     *
     * @param  integer  $page
     * @return integer
     */
    public function setPage(integer $page)
    {
        return $this->page = $page;
    }

    /**
     * Get the x coordinate of the signature rectangle
     *
     * @return integer
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Set the x coordinate of the signature rectangle
     *
     * @param  integer  $x
     * @return integer
     */
    public function setX(integer $x)
    {
        return $this->x = $x;
    }

    /**
     * Get the y coordinate of the signature rectangle
     *
     * @return integer
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * Set the y coordinate of the signature rectangle
     *
     * @param  integer  $y
     * @return integer
     */
    public function setY(integer $y)
    {
        return $this->y = $y;
    }

    /**
     * Get the width of the signature rectangle
     *
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set the width of the signature rectangle
     *
     * @param  integer  $width
     * @return integer
     */
    public function setWidth(integer $width)
    {
        return $this->width = $width;
    }

    /**
     * Get the height of the signature rectangle
     *
     * @return integer
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set the height of the signature rectangle
     *
     * @param  integer  $height
     * @return integer
     */
    public function setHeight(integer $height)
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
