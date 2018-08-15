<?php

namespace Helori\PhpSign\Drivers;

use Helori\PhpSign\Elements\Scenario;


interface DriverInterface
{
    /**
     * Initialize a transaction from a scenario
     *
     * @param  Helori\PhpSign\Elements\Scenario  $scenario
     * @return array
     */
    public function initTransaction(Scenario $scenario);
}
