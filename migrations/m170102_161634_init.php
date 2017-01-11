<?php

use yii\db\Migration;

class m170102_161634_init extends Migration
{


    // Use safeUp/safeDown to run migration code within a transaction
    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
    }

    public function safeDown()
    {
        $this->removeForeignKeys();
        $this->removeIndexes();
        $this->removeTables();
    }

    private function createIndexes(){
        // creates index for column `factorNumber`
        $this->createIndex(
            'idx-transaction-factorNumber',
            '{{%transaction}}',
            'factorNumber',
            true
        );

        // creates index for column `transId`
        $this->createIndex(
            'idx-transaction-transId',
            '{{%transaction}}',
            'transId',
            true
        );

        // creates index for column `transId`
        $this->createIndex(
            'idx-transaction_log-transId',
            '{{%transaction_log}}',
            'transId'
        );
    }

    private function removeIndexes(){
        // drop index for column `factorNumber`
        $this->dropIndex(
            'idx-transaction-factorNumber',
            '{{%transaction}}'
        );

        // drop index for column `transId`
        $this->dropIndex(
            'idx-transaction-transId',
            '{{%transaction}}'
        );

        // drop index for column `transId`
        $this->dropIndex(
            'idx-transaction_log-transId',
            '{{%transaction_log}}'
        );
    }

    private function addForeignKeys(){
        // add foreign key for table `transaction_data` and `transaction`
        $this->addForeignKey(
            'fk-transaction_data-transactionId',
            '{{%transaction_data}}',
            'transactionId',
            '{{%transaction}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // add foreign key for table `transaction_cardholder` and `transaction`
        $this->addForeignKey(
            'fk-transaction_cardholder-transactionId',
            '{{%transaction_cardholder}}',
            'transactionId',
            '{{%transaction}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // add foreign key for table `transaction_log` and `transaction`
        $this->addForeignKey(
            'fk-transaction_log-transId',
            '{{%transaction_log}}',
            'transId',
            '{{%transaction}}',
            'transId',
            'CASCADE',
            'CASCADE'
        );
    }

    private function removeForeignKeys(){
        // remove foreign key for table `transaction_data` and `transaction`
        $this->dropForeignKey(
            'fk-transaction_data-transactionId',
            '{{%transaction_data}}'
        );

        // remove foreign key for table `transaction_cardholder` and `transaction`
        $this->dropForeignKey(
            'fk-transaction_cardholder-transactionId',
            '{{%transaction_cardholder}}'
        );

        // remove foreign key for table `transaction_log` and `transaction`
        $this->dropForeignKey(
            'fk-transaction_log-transId',
            '{{%transaction_log}}'
        );
    }

    private function createTables(){
        //Create transaction table.
        $this->createTable('{{%transaction}}', [
            'id'=>$this->primaryKey(),
            'factorNumber'=>$this->string()->notNull(),
            'transId'=>$this->string()->notNull(),
            'price'=>$this->integer(10)->notNull(),
            'transBankName'=>$this->string(),
            'transTrackingCode'=>$this->string(),
            'type'=>"tinyint(2) NOT NULL DEFAULT 1",
            'status'=>"tinyint(2) NOT NULL DEFAULT 1",
            'ip'=>$this->string(),
            'payTime'=>$this->integer(20)->notNull(),
            'createTime'=>$this->integer(20)->notNull(),
        ]);

        //Create transaction_data table to
        $this->createTable('{{%transaction_data}}', [
            'transactionId'=>$this->primaryKey(),
            'request'=>$this->text(),
            'response'=>$this->text(),
            'responseStatus'=>"tinyint(2) NOT NULL DEFAULT 1",
            'responseTime'=>$this->integer(20),
            'inquiryResponse'=>$this->text(),
            'inquiryStatus'=>"tinyint(2) NOT NULL DEFAULT 1",
            'inquiryTime'=>$this->integer(20),
        ]);

        //Create transaction_cardholder table
        $this->createTable('{{%transaction_cardholder}}', [
            'transactionId'=>$this->primaryKey(),
            'bankName'=>$this->string(64),
            'cardNumber'=>$this->string(128),
            'accountNumber'=>$this->string(128),
            'accountOwner'=>$this->string(),
            'tel'=>$this->string(64),
            'createTime'=>$this->integer(20)
        ]);

        //Create transaction_log table
        $this->createTable('{{%transaction_log}}', [
            'id'=>$this->primaryKey(),
            'transId'=>$this->string()->notNull(),
            'bank'=>$this->string(),
            'status'=>$this->string(),
            'request'=>$this->text(),
            'response'=>$this->text(),
            'responseCode'=>$this->text(),
            'description'=>$this->text(),
            'ip'=>$this->string(),
            'time'=>$this->integer(20)
        ]);
    }

    private function removeTables(){
        //Drop transaction table.
        $this->dropTable('{{%transaction}}');

        //Drop transaction_data table to
        $this->dropTable('{{%transaction_data}}');

        //Drop transaction_cardholder table
        $this->dropTable('{{%transaction_cardholder}}');

        //Drop transaction_log table
        $this->dropTable('{{%transaction_log}}');
    }

}
