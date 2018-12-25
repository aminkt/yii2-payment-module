<?php

namespace aminkt\yii2\payment\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "{{%transaction_sessions}}".
 *
 * @property integer $id             Transaction session id.
 * @property string $order_id        Order tracking code
 * @property string $authority      Bank transaction id. unique for every session.
 * @property string $psp            Name of psp.
 * @property double $amount         Amount of transaction.
 * @property string $tracking_code   Bank tracking code to track transaction later.
 * @property string $description    Description of transaction.
 * @property string $note           Operator note of transaction.
 * @property integer $status         Status of transaction.
 * @property integer $type           Type of transaction. Cart-to-cart or internet gate.
 * @property string $user_card_pan    Card number of user.
 * @property string $user_card_hash   Card hashed string.
 * @property string $user_mobile     Mobile of user.
 * @property string $ip             Ip of user that create transaction.
 * @property string $updated_at       Update time of session.
 * @property string $created_at       Create time of session.
 *
 * @property TransactionInquiry[] $inquiries
 * @property TransactionLog[] $logs
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class TransactionSession extends ActiveRecord
{
    const STATUS_NOT_PAID = 1;
    const STATUS_PAID = 2;
    const STATUS_FAILED = 3;
    const STATUS_INQUIRY_PROBLEM = 4;

    const TYPE_WEB_BASE = 1;
    const TYPE_CART_TO_CART = 2;

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
        return '{{%transaction_sessions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['status', 'type'], 'integer'],
            [['amount'], 'number'],
            [['description', 'note', 'psp'], 'string'],
            [['updated_at', 'created_at', 'order_id', 'authority', 'tracking_code'], 'safe'],
            [['user_card_pan', 'user_card_hash'], 'string', 'max' => 255],
            [['user_mobile'], 'string', 'max' => 15],
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
            'order_id' => 'شناسه سفارش',
            'authority' => 'شناسه پرداخت',
            'psp' => 'نام درگاه',
            'amount' => 'مبلغ',
            'tracking_code' => 'کد پیگیری',
            'description' => 'توضیحات',
            'note' => 'یادآوری',
            'status' => 'وضعیت',
            'type' => 'نوع پرداخت',
            'user_card_pan' => 'شماره کارت مشتری',
            'user_card_hash' => 'هش کارت مشتری',
            'user_mobile' => 'تلفن مشتری',
            'ip' => 'Ip',
            'updated_at' => 'تاریخ ویرایش',
            'created_at' => 'تاریخ ایجاد',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(TransactionLog::className(), ['session_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInquiries()
    {
        return $this->hasMany(TransactionInquiry::className(), ['session_id' => 'id']);
    }
}
