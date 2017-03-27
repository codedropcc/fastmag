<?php

use Phinx\Migration\AbstractMigration;

class InitTables extends AbstractMigration {
    public function up() {
        $sql = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'database.sql');
        $this->execute($sql);
    }
}
