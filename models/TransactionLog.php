<?php

namespace payment\models;

/**
 * This is the model class for table "{{%transaction_log}}".
 *
 * @property integer $id
 * @property string $transId
 * @property string $bank
 * @property string $status
 * @property string $request
 * @property string $response
 * @property string $responseCode
 * @property string $description
 * @property string $ip
 * @property integer $time
 *
 * @property Transaction $trans
 */
class TransactionLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction_log}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['transId'], 'required'],
            [['request', 'response', 'responseCode', 'description'], 'string'],
            [['time'], 'integer'],
            [['transId', 'bank', 'status', 'ip'], 'string', 'max' => 255],
            [['transId'], 'exist', 'skipOnError' => true, 'targetClass' => Transaction::className(), 'targetAttribute' => ['transId' => 'transId']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transId' => 'Trans ID',
            'bank' => 'Bank',
            'status' => 'Status',
            'request' => 'Request',
            'response' => 'Response',
            'responseCode' => 'Response Code',
            'description' => 'Description',
            'ip' => 'Ip',
            'time' => 'Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTrans()
    {
        return $this->hasOne(Transaction::className(), ['transId' => 'transId']);
    }
}
