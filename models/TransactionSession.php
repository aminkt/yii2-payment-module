<?php

namespace aminkt\payment\models;

/**
 * This is the model class for table "{{%transaction_sessions}}".
 *
 * @property integer $id
 * @property integer $orderId
 * @property string $authority
 * @property string $psp
 * @property double $amount
 * @property string $trackingCode
 * @property string $description
 * @property string $note
 * @property integer $status
 * @property integer $type
 * @property string $userCardPan
 * @property string $userCardHash
 * @property string $userMobile
 * @property string $ip
 * @property string $updateAt
 * @property string $createAt
 *
 * @property TransactionInquiry[] $inquiries
 * @property TransactionLog[] $logs
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class TransactionSession extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction_sessions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderId'], 'required'],
            [['orderId', 'status', 'type'], 'integer'],
            [['amount'], 'number'],
            [['description', 'note', 'psp'], 'string'],
            [['updateAt', 'createAt'], 'safe'],
            [['authority', 'trackingCode', 'userCardPan', 'userCardHash'], 'string', 'max' => 255],
            [['userMobile'], 'string', 'max' => 15],
            [['ip'], 'string', 'max' => 25],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderId' => 'Order ID',
            'authority' => 'Authority',
            'psp' => 'Payment system provider',
            'amount' => 'Amount',
            'trackingCode' => 'Tracking Code',
            'description' => 'Description',
            'note' => 'Note',
            'status' => 'Status',
            'type' => 'Type',
            'userCardPan' => 'User Card Pan',
            'userCardHash' => 'User Card Hash',
            'userMobile' => 'User Mobile',
            'ip' => 'Ip',
            'updateAt' => 'Update At',
            'createAt' => 'Create At',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(TransactionLog::className(), ['sessionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiries()
    {
        return $this->hasMany(TransactionInquiry::className(), ['sessionId' => 'id']);
    }
}
