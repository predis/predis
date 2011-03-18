<?php

namespace Predis;

interface IConnectionSchemes {
    public function newConnection($parameters);
}
