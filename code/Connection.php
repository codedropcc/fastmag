<?php

namespace Fastmag;

use Pixie\Connection as PixieConnection;

class Connection {
    protected $_connection;
    protected static $instance = null;
    
    protected $tablePrefix;

    // @codeCoverageIgnoreStart
    protected function __construct($host, $dbname, $user, $pass, $prefix) {
        $config = [
            'driver' => 'mysql',
            'host' => $host,
            'database' => $dbname,
            'username' => $user,
            'password' => $pass,
            'charset' => 'utf8',
            'prefix' => $prefix,
        ];
        $this->tablePrefix = $prefix;
        $this->_connection = new PixieConnection('mysql', $config, 'Fastmag\\QB');
    }

    public function __call($name, $arguments) {
        call_user_func_array([$this->_connection, $name], $arguments);
    }

    private function __clone() {}
    private function __wakeup() {}

    public static function getInstance($host = null, $dbname = null, $user = null, $pass = null, $prefix = null) {
        if (static::$instance === null) {
            if ($host == null && class_exists('\Mage')) {
                $config = \Mage::getConfig()->getResourceConnectionConfig('default_setup');
                $host = $config->host;
                $dbname = $config->dbname;
                $user = $config->username;
                $pass = $config->password;
                $prefix = \Mage::getConfig()->getTablePrefix();
            }
            static::$instance = new static($host, $dbname, $user, $pass, $prefix);
        }
        return static::$instance;
    }
    // @codeCoverageIgnoreEnd
    
    public function getPrefix() {
        return $this->tablePrefix;
    }
}
