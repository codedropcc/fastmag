<?php

use function DI\object;
use function DI\get;

use Fastmag\Connection;
use Fastmag\AttributeHelper;

return [
    Connection::class => function () {
        return new Connection('mysql', 'magento', 'magento', 'magento', null);
    },
    AttributeHelper::class => function (Connection $conn) {
        return new AttributeHelper($conn);
    },
];
