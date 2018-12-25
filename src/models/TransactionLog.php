<?php

namespace aminkt\payment\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "{{%transaction_log}}".
 *
 * @property integer $id
 * @property string $sessionId
 * @property string $bankDriver
 * @property string $status
 * @property string $request
 * @property string $response
 * @property string $responseCode
 * @property string $description
 * @property string $ip
 * @property string $time
 *
 * @property TransactionSession $transactionSession
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class TransactionLog extends ActiveRecord
{
    const STATUS_PAYMENT_REQ = "payment_request";
    const STATUS_PAYMENT_VERIFY = "payment_verify";
    const STATUS_PAYMENT_INQUIRY = "payment_inquiry";
    const STATUS_UNKNOWN = "unknown";

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['time'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['time'],
                ],
                // if you're using datetime instead of UNIX timestamp:
                'value' => new Expression('NOW()'),
            ],
        ];
    }

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
            [['sessionId'], 'required'],
            [['request', 'response', 'responseCode', 'description'], 'string'],
            [['time'], 'safe'],
            [['sessionId', 'bankDriver', 'status', 'ip'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sessionId' => 'Session ID',
            'bankDriver' => 'Bank Driver',
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
    public function getTransactionSession()
    {
        return $this->hasOne(TransactionSession::className(), ['id' => 'sessionId']);
    }
}
