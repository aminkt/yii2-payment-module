<?php

use yii\db\Migration;

class m170102_161634_init extends Migration
{
    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTables();
        $this->addForeignKeys();
    }

    public function safeDown()
    {
        $this->removeForeignKeys();
        $this->removeTables();
    }


    private function addForeignKeys(){

        // add foreign key for table `inquiries` and `transaction_sessions`
        $this->addForeignKey(
            'fk-inquiries-sessionId',
            '{{%transaction_inquiries}}',
            'session_id',
            '{{%transaction_sessions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // add foreign key for table `transaction_log` and `transaction_sessions`
        $this->addForeignKey(
            'fk-transactionLog-sessionId',
            '{{%transaction_log}}',
            'session_id',
            '{{%transaction_sessions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    private function removeForeignKeys(){
        // remove foreign key for table `inquiries` and `transaction_sessions`
        $this->dropForeignKey(
            'fk-inquiries-sessionId',
            '{{%transaction_inquiries}}'
        );

        // remove foreign key for table `transaction_log` and `transaction_sessions`
        $this->dropForeignKey(
            'fk-transactionLog-sessionId',
            '{{%transaction_log}}'
        );
    }

    private function createTables(){
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        // Store transaction sessions.
        $this->createTable('{{%transaction_sessions}}', [
            'id'=>$this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'psp' => $this->string(),
            'authority' => $this->string(),
            'amount' => $this->double()->defaultValue(0),
            'tracking_code' => $this->string(),
            'description' => $this->text(),
            'note' => $this->text(),
            'status' => $this->smallInteger(1)->defaultValue(1),
            'type' => $this->smallInteger(1)->defaultValue(1),
            'user_card_pan' => $this->string(),
            'user_card_hash' => $this->string(),
            'user_mobile' => $this->string(15),
            'ip' => $this->string(25),
            'updated_at' => $this->dateTime(),
            'created_at' => $this->dateTime(),

        ], $tableOptions);

        $this->createTable('{{%transaction_inquiries}}', [
            'id' => $this->primaryKey(),
            'session_id' => $this->integer()->notNull(),
            'status' => $this->smallInteger(1)->defaultValue(1),
            'description' => $this->text(),
            'updated_at' => $this->dateTime(),
            'created_at' => $this->dateTime(),
        ], $tableOptions);

        //Create transaction_log table
        $this->createTable('{{%transaction_log}}', [
            'id'=>$this->primaryKey(),
            'session_id' => $this->integer()->notNull(),
            'bank_driver' => $this->string(),
            'status'=>$this->string(),
            'request'=>$this->text(),
            'response'=>$this->text(),
            'response_code'=>$this->text(),
            'description'=>$this->text(),
            'ip'=>$this->string(),
            'time' => $this->dateTime()
        ], $tableOptions);
    }

    private function removeTables(){
        //Drop transaction table.
        $this->dropTable('{{%transaction_sessions}}');


        //Drop transaction_cardholder table
        $this->dropTable('{{%transaction_inquiries}}');

        //Drop transaction_log table
        $this->dropTable('{{%transaction_log}}');
    }

}
