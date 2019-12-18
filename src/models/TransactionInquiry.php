<?php

namespace aminkt\yii2\payment\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "{{%transaction_inquiries}}".
 *
 * @property integer $id
 * @property integer $session_id
 * @property integer $status
 * @property string $description
 * @property string $updated_at
 * @property string $created_at
 *
 * @property \aminkt\yii2\payment\models\TransactionSession $transactionSession
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
                    ActiveRecord::EVENT_BEFORE_INSERT => ['updated_at', 'created_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
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
            [['session_id'], 'required'],
            [['session_id', 'status'], 'integer'],
            [['description'], 'string'],
            [['updated_at', 'created_at'], 'safe'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransactionSession()
    {
        return $this->hasOne(TransactionSession::className(), ['id' => 'session_id']);
    }
}
