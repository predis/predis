<?php

namespace Predis\Commands;

use Predis\Distribution\IDistributionStrategy;

interface ICommand {
    public function getId();
    public function canBeHashed();
    public function getHash(IDistributionStrategy $distributor);
    public function setArgumentsArray(Array $arguments);
    public function getArguments();
    public function parseResponse($data);
}
