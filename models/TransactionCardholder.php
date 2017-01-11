<?php

namespace payment\models;

/**
 * This is the model class for table "{{%transaction_cardholder}}".
 *
 * @property integer $transactionId
 * @property string $bankName
 * @property string $cardNumber
 * @property string $accountNumber
 * @property string $accountOwner
 * @property string $tel
 * @property integer $createTime
 *
 * @property Transaction $transaction
 */
class TransactionCardholder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction_cardholder}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['createTime'], 'integer'],
            [['bankName', 'tel'], 'string', 'max' => 64],
            [['cardNumber', 'accountNumber'], 'string', 'max' => 128],
            [['accountOwner'], 'string', 'max' => 255],
            [['transactionId'], 'exist', 'skipOnError' => true, 'targetClass' => Transaction::className(), 'targetAttribute' => ['transactionId' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'transactionId' => 'Transaction ID',
            'bankName' => 'Bank Name',
            'cardNumber' => 'Card Number',
            'accountNumber' => 'Account Number',
            'accountOwner' => 'Account Owner',
            'tel' => 'Tel',
            'createTime' => 'Create Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransaction()
    {
        return $this->hasOne(Transaction::className(), ['id' => 'transactionId']);
    }
}
