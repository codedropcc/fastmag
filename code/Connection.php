<?php

namespace Fastmag;

use Pixie\Connection as PixieConnection;

class Connection {
    protected $_connection;
    protected static $instance = null;
    
    protected $tablePrefix;

    // @codeCoverageIgnoreStart
    public function __construct($host, $dbname, $user, $pass, $prefix) {
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
    // @codeCoverageIgnoreEnd
    
    public function getPrefix() {
        return $this->tablePrefix;
    }
}
