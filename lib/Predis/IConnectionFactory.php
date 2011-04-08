<?php

namespace Predis;

interface IConnectionFactory {
    public function create($parameters);
}
