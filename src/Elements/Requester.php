<?php

namespace Helori\PhpSign\Elements;

use Helori\PhpSign\Exceptions\ValidationException;
use Helori\PhpSign\Drivers\DriverInterface;


class Requester
{
    /**
     * The driver that will be used
     *
     * @var \Helori\PhpSign\Drivers\DriverInterface
     */
    protected $driver;

    /**
     * Create a new Requester instance.
     *
     * @param  string  $driverName
     * @param  array  $driverConfig
     * @return void
     */
    public function __construct(string $driverName, array $driverConfig)
    {
        $name = '\\Helori\\PhpSign\\Drivers\\'.ucfirst(strtolower($driverName)).'Driver';

        if(class_exists($name) && is_subclass_of($name, DriverInterface::class)){

            $this->driver = new $name($driverConfig);

        }else{

            throw new ValidationException('Unknown driver name "'.$driverName.'" with class name "'.$name.'"');
        }
    }

    /**
     * Get the driver
     *
     * @return \Helori\PhpSign\Drivers\DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Create a transaction from a scenario
     *
     * @param  \Helori\PhpSign\Elements\Scenario  $scenario
     * @return array
     */
    public function createTransaction(Scenario $scenario)
    {
        return $this->driver->createTransaction($scenario);
    }

    /**
     * Get a transaction
     *
     * @param  string  $transactionId
     * @return \Helori\PhpSign\Elements\Transaction
     */
    public function getTransaction(string $transactionId)
    {
        return $this->driver->getTransaction($transactionId);
    }

    /**
     * Get transaction documents
     *
     * @param  string  $transactionId
     * @return array
     */
    public function getDocuments(string $transactionId)
    {
        return $this->driver->getDocuments($transactionId);
    }

    /**
     * Cancel a transaction
     *
     * @param  string  $transactionId
     * @return \Helori\PhpSign\Elements\Transaction
     */
    public function cancelTransaction(string $transactionId)
    {
        return $this->driver->cancelTransaction($transactionId);
    }

    /**
     * Get the driver's specific expiration days
     *
     * @return int
     */
    public function getExpirationDays()
    {
        return $this->driver->getExpirationDays();
    }

    /**
     * Convert a native (driver specific) webhook request into the common webhook data format
     *
     * @param  array  $requestData
     * @return \Helori\PhpSign\Elements\Webhook
     */
    public function formatWebhook(array $requestData)
    {
        return $this->driver->formatWebhook($requestData);
    }
}
