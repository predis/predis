<?php

namespace Predis;

use Predis\Distribution\IDistributionStrategy;

interface ICommand {
    public function getCommandId();
    public function canBeHashed();
    public function closesConnection();
    public function getHash(IDistributionStrategy $distributor);
    public function setArgumentsArray(Array $arguments);
    public function getArguments();
    public function parseResponse($data);
}
