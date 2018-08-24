<?php

namespace Helori\PhpSign\Elements;


class Transaction
{
    const STATUS_DRAFT = 'draft';
    const STATUS_READY = 'ready';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELED = 'canceled';
    const STATUS_REFUSED = 'canceled';
    const STATUS_FAILED = 'failed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_UNKNOWN = 'unknown';

    /**
     * The transaction id
     *
     * @var string
     */
    protected $id;

    /**
     * The transaction status
     *
     * @var string
     */
    protected $status;

    /**
     * The transaction signers infos
     *
     * @var array
     */
    protected $signersInfos;

    /**
     * The transaction signed files
     *
     * @var array
     */
    protected $signedFiles;

    /**
     * The transaction original data (from the driver)
     *
     * @var data
     */
    protected $data;

    /**
     * Create a new TransactionInfo instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the transaction id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the transaction id
     *
     * @param  string  $id
     * @return string
     */
    public function setId(string $id)
    {
        return $this->id = $id;
    }

    /**
     * Get the transaction status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set the transaction status
     *
     * @param  string  $name
     * @return string
     */
    public function setStatus(string $status)
    {
        return $this->status = $status;
    }

    /**
     * Get the transaction signers infos
     *
     * @return array
     */
    public function getSignersInfos()
    {
        return $this->signersInfos;
    }

    /**
     * Set the transaction signers infos
     *
     * @param  array  $signersInfos
     * @return array
     */
    public function setSignersInfos(array $signersInfos)
    {
        return $this->signersInfos = $signersInfos;
    }

    /**
     * Get the transaction signed files
     *
     * @return array
     */
    public function getSignedFiles()
    {
        return $this->signedFiles;
    }

    /**
     * Set the transaction signers infos
     *
     * @param  array  $signedFiles
     * @return array
     */
    public function setSignedFiles(array $signedFiles)
    {
        return $this->signedFiles = $signedFiles;
    }

    /**
     * Get the transaction original data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the transaction original data
     *
     * @param  array  $data
     * @return array
     */
    public function setData(array $data)
    {
        return $this->data = $data;
    }

    /**
     * Convert the transaction to an associative array
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'signersInfos' => $this->signersInfos,
            'signedFiles' => $this->signedFiles,
            'data' => $this->data,
        ];
    }
}
