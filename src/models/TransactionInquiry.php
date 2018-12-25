<?php

namespace aminkt\yii2\payment\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

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
 * @property \aminkt\payment\models\TransactionSession $transactionSession
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class TransactionInquiry extends ActiveRecord
{
    const STATUS_INQUIRY_WAITING = 1;
    const STATUS_INQUIRY_SUCCESS = 2;
    const STATUS_INQUIRY_FAILED = 3;

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['updateAt', 'createAt'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updateAt'],
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
