<?php

namespace Predis;

interface IConnectionFactory {
    public function newConnection($parameters);
}
