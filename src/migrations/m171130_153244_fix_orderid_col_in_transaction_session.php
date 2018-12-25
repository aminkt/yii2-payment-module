<?php

use yii\db\Migration;

/**
 * Class m171130_153244_fix_orderid_col_in_transaction_session
 */
class m171130_153244_fix_orderid_col_in_transaction_session extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%transaction_sessions}}', 'order_id', $this->string()->notNull());
        $this->createIndex('transactionSession_orderId_index', '{{%transaction_sessions}}', 'order_id', false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropIndex('transactionSession_orderId_index', '{{%transaction_sessions}}');
        $this->alterColumn('{{%transaction_sessions}}', 'order_id', $this->integer()->notNull());
    }
}
