<?php

namespace Predis;

class Autoloader {
    private $base_directory;
    private $prefix;

    public function __construct($base_directory=NULL) {
        $this->base_directory = $base_directory ?: dirname(__FILE__);
        $this->prefix = __NAMESPACE__ . '\\';
    }

    public static function register() {
        spl_autoload_register(array(new self, 'autoload'));
    }

    public function autoload($class_name) {
        if (0 !== strpos($class_name, $this->prefix)) {
            return;
        }

        $relative_class_name = substr($class_name, strlen($this->prefix));
        $class_name_parts = explode('\\', $relative_class_name);

        $path = $this->base_directory .
            DIRECTORY_SEPARATOR .
            implode(DIRECTORY_SEPARATOR, $class_name_parts) .
            '.php';

        require_once $path;
    }
}
