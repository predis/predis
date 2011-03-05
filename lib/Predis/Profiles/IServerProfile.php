<?php

namespace Predis\Profiles;

interface IServerProfile {
    public function getVersion();
    public function supportsCommand($command);
    public function supportsCommands(Array $commands);
    public function defineCommand($command, $aliases);
    public function defineCommands(Array $commands);
    public function createCommand($method, $arguments = array());
}
