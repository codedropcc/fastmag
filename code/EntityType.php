<?php

namespace Fastmag;

use Fastmag\QB;
use Fastmag\FlatEntity;

/**
 * @Injectable(scope="prototype")
 */
class EntityType extends FlatEntity {
    public function table() {
        return 'eav_entity_type';
    }

    public function customOptionsSave() {

    }
}
