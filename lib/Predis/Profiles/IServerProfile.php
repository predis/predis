<?php

namespace Predis\Profiles;

interface IServerProfile {
    public function getVersion();
    public function supportsCommand($command);
    public function supportsCommands(Array $commands);
    public function registerCommand($command, $aliases);
    public function registerCommands(Array $commands);
    public function createCommand($method, $arguments = array());
}
