<?php

use yii\db\Migration;

class m170109_114550_fix_transaction_tbl extends Migration
{
    public function up()
    {
        $this->alterColumn('{{%transaction}}', 'transId', $this->string()->null()->defaultValue(null));
        $this->alterColumn('{{%transaction}}', 'payTime', $this->integer(20)->notNull()->defaultValue(0));
    }

    public function down()
    {
        $this->alterColumn('{{%transaction}}', 'transId', $this->string()->notNull());
        $this->alterColumn('{{%transaction}}', 'payTime', $this->integer(20)->notNull());
    }
}
