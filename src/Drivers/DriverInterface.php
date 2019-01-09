<?php

namespace Helori\PhpSign\Drivers;

use Helori\PhpSign\Elements\Scenario;


interface DriverInterface
{
	/**
     * Get the driver's name
     *
     * @return string
     */
    public function getName();

    /**
     * Create a transaction from a scenario
     *
     * @param  Helori\PhpSign\Elements\Scenario  $scenario
     * @return Transaction
     */
    public function createTransaction(Scenario $scenario);

    /**
     * Get transaction info
     *
     * @param  string  $transactionId
     * @return Transaction
     */
    public function getTransaction(string $transactionId);

    /**
     * Get transaction documents
     *
     * @param  string  $transactionId
     * @return array
     */
    public function getDocuments(string $transactionId);

    /**
     * Cancel a transaction
     *
     * @param  string  $transactionId
     * @return \Helori\PhpSign\Elements\Transaction
     */
    public function cancelTransaction(string $transactionId);
}
