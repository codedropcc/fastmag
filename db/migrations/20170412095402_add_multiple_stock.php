<?php

use Phinx\Migration\AbstractMigration;

class AddMultipleStock extends AbstractMigration
{
    public function up()
    {
        $this->execute("INSERT INTO cataloginventory_stock (`stock_name`) VALUES ('test_stock')");
    }

    public function down()
    {
        $this->execute("DELETE FROM cataloginventory_stock WHERE stock_name = 'test_stock'");
    }
}
