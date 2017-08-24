<?php

namespace aminkt\payment\models;

use Yii;

/**
 * This is the model class for table "{{%transaction_inquiries}}".
 *
 * @property integer $id
 * @property integer $sessionId
 * @property integer $status
 * @property string $description
 * @property string $updateAt
 * @property string $createAt
 *
 * @property TransactionSession $transactionSession
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class TransactionInquiry extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction_inquiries}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['sessionId'], 'required'],
            [['sessionId', 'status'], 'integer'],
            [['description'], 'string'],
            [['updateAt', 'createAt'], 'safe'],
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
            'status' => 'Status',
            'description' => 'Description',
            'updateAt' => 'Update At',
            'createAt' => 'Create At',
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
