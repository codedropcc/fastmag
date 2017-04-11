<?php

use function DI\object;
use function DI\get;

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
                'host' => getenv('DATABASE_HOST') ?: 'localhost',
                'dbname' => getenv('DATABASE_NAME') ?: 'magento',
                'username' => getenv('DATABASE_USERNAME') ?: 'magento',
                'password' => getenv('DATABASE_PASSWORD') ?: '',
                'prefix' => '',
            ];
        }

        return new Connection($config['host'], $config['dbname'], $config['username'], $config['password'], $config['prefix']);
    },
    AttributeHelper::class => function (Connection $conn) {
        return new AttributeHelper($conn);
    },
];
