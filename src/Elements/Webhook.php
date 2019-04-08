<?php

namespace Helori\PhpSign\Elements;


class Webhook
{
    /**
     * The transaction's id
     *
     * @var string
     */
    protected $transactionId;

    /**
     * The transaction's status
     *
     * @var string
     */
    protected $transactionStatus;

    /**
     * Create a new Signer instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Get the transaction's id
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Set the transaction's id
     *
     * @param  string  $transactionId
     * @return string
     */
    public function setTransactionId(string $transactionId)
    {
        return $this->transactionId = $transactionId;
    }

    /**
     * Get the transaction's status
     *
     * @return string
     */
    public function getTransactionStatus()
    {
        return $this->transactionStatus;
    }

    /**
     * Set the transaction's status
     *
     * @param  string  $transactionStatus
     * @return string
     */
    public function setTransactionStatus(string $transactionStatus)
    {
        return $this->transactionStatus = $transactionStatus;
    }
}
