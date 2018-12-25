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
            'sessionId',
            '{{%transaction_sessions}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // add foreign key for table `transaction_log` and `transaction_sessions`
        $this->addForeignKey(
            'fk-transactionLog-sessionId',
            '{{%transaction_log}}',
            'sessionId',
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
            'orderId' => $this->integer()->notNull(),
            'psp' => $this->string(),
            'authority' => $this->string(),
            'amount' => $this->double()->defaultValue(0),
            'trackingCode' => $this->string(),
            'description' => $this->text(),
            'note' => $this->text(),
            'status' => $this->smallInteger(1)->defaultValue(1),
            'type' => $this->smallInteger(1)->defaultValue(1),
            'userCardPan' => $this->string(),
            'userCardHash' => $this->string(),
            'userMobile' => $this->string(15),
            'ip' => $this->string(25),
            'updateAt' => $this->dateTime(),
            'createAt' => $this->dateTime(),

        ], $tableOptions);

        $this->createTable('{{%transaction_inquiries}}', [
            'id' => $this->primaryKey(),
            'sessionId' => $this->integer()->notNull(),
            'status' => $this->smallInteger(1)->defaultValue(1),
            'description' => $this->text(),
            'updateAt' => $this->dateTime(),
            'createAt' => $this->dateTime(),
        ], $tableOptions);

        //Create transaction_log table
        $this->createTable('{{%transaction_log}}', [
            'id'=>$this->primaryKey(),
            'sessionId' => $this->integer()->notNull(),
            'bankDriver' => $this->string(),
            'status'=>$this->string(),
            'request'=>$this->text(),
            'response'=>$this->text(),
            'responseCode'=>$this->text(),
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
