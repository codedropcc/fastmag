<?php

namespace Fastmag;

use DI\ContainerBuilder;
use Fastmag\Exception;

final class Fastmag {
    protected $container = null;
    protected static $instance = null;

    // @codeCoverageIgnoreStart
    protected function __construct($config = __DIR__ . '/config.php') {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->addDefinitions($config);
        $containerBuilder->useAnnotations(true);
        $this->container = $containerBuilder->build();
    }

    private function __clone() {

    }

    private function __wakeup() {

    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new static();
        }
        return self::$instance;
    }
    // @codeCoverageIgnoreEnd

    public function getModel($name) {
        if (!is_string($name)) {
            throw new Exception('Name of model should be string!');
        }
        return $this->container->get($name);
    }
}
