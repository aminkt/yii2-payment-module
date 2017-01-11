<?php

namespace payment\models;

/**
 * This is the model class for table "{{%transaction_data}}".
 *
 * @property integer $transactionId
 * @property string $request
 * @property string $response
 * @property integer $responseStatus
 * @property integer $responseTime
 * @property string $inquiryResponse
 * @property integer $inquiryStatus
 * @property integer $inquiryTime
 *
 * @property Transaction $transaction
 */
class TransactionData extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction_data}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['request', 'response', 'inquiryResponse'], 'string'],
            [['responseStatus', 'responseTime', 'inquiryStatus', 'inquiryTime'], 'integer'],
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
            'request' => 'Request',
            'response' => 'Response',
            'responseStatus' => 'Response Status',
            'responseTime' => 'Response Time',
            'inquiryResponse' => 'Inquiry Response',
            'inquiryStatus' => 'Inquiry Status',
            'inquiryTime' => 'Inquiry Time',
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
