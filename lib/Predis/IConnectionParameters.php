<?php

namespace Predis;

interface IConnectionParameters {
    public function __isset($parameter);
    public function __get($parameter);
    public function toArray();
}
