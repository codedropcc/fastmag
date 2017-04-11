<?php

use function DI\object;
use function DI\get;
use function DI\env;

use Fastmag\Connection;
use Fastmag\AttributeHelper;

return [
    Connection::class => function () {
        $config = [];
        if (class_exists(\Mage::class)) {
            $connectionObj = \Mage::getConfig()->getResourceConnectionConfig("default_setup");
            $config = [
                'host' => $connectionObj->host,
                'dbname' => $connectionObj->dbname,
                'username' => $connectionObj->usename,
                'password' => $connectionObj->password,
                'prefix' => \Mage::getConfig()->getTablePrefix(),
            ];
        }
        else {
            $config = [
                'host' => DI\env('DATABASE_HOST', 'localhost'),
                'dbname' => DI\env('DATABASE_NAME','magento'),
                'username' => DI\env('DATABASE_USERNAME','magento'),
                'password' => DI\env('DATABASE_PASSWORD','magento'),
                'prefix' => '',
            ];
        }

        return new Connection($config['host'], $config['dbname'], $config['username'], $config['password'], $config['prefix']);
    },
    AttributeHelper::class => function (Connection $conn) {
        return new AttributeHelper($conn);
    },
];
