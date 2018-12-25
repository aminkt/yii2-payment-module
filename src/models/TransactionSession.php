<?php

namespace aminkt\payment\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * This is the model class for table "{{%transaction_sessions}}".
 *
 * @property integer $id             Transaction session id.
 * @property string $orderId        Order tracking code
 * @property string $authority      Bank transaction id. unique for every session.
 * @property string $psp            Name of psp.
 * @property double $amount         Amount of transaction.
 * @property string $trackingCode   Bank tracking code to track transaction later.
 * @property string $description    Description of transaction.
 * @property string $note           Operator note of transaction.
 * @property integer $status         Status of transaction.
 * @property integer $type           Type of transaction. Cart-to-cart or internet gate.
 * @property string $userCardPan    Card number of user.
 * @property string $userCardHash   Card hashed string.
 * @property string $userMobile     Mobile of user.
 * @property string $ip             Ip of user that create transaction.
 * @property string $updateAt       Update time of session.
 * @property string $createAt       Create time of session.
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
        return '{{%transaction_sessions}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderId'], 'required'],
            [['status', 'type'], 'integer'],
            [['amount'], 'number'],
            [['description', 'note', 'psp'], 'string'],
            [['updateAt', 'createAt', 'orderId', 'authority', 'trackingCode'], 'safe'],
            [['userCardPan', 'userCardHash'], 'string', 'max' => 255],
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
            'orderId' => 'شناسه سفارش',
            'authority' => 'شناسه پرداخت',
            'psp' => 'نام درگاه',
            'amount' => 'مبلغ',
            'trackingCode' => 'کد پیگیری',
            'description' => 'توضیحات',
            'note' => 'یادآوری',
            'status' => 'وضعیت',
            'type' => 'نوع پرداخت',
            'userCardPan' => 'شماره کارت مشتری',
            'userCardHash' => 'هش کارت مشتری',
            'userMobile' => 'تلفن مشتری',
            'ip' => 'Ip',
            'updateAt' => 'تاریخ ویرایش',
            'createAt' => 'تاریخ ایجاد',
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

    /**
     * Get const label
     *
     * @param $label
     * @param string $type
     *
     * @return string
     *
     * @author Saghar Mojdehi <saghar.mojdehi@gmail.com>
     */
    public static function getLabel($label, $type = 'status')
    {
        if ($type == 'status') {
            switch ($label) {
                case self::STATUS_NOT_PAID:
                    return 'پرداخت نشده';
                    break;
                case self::STATUS_PAID:
                    return 'پرداخت شده';
                    break;
                case self::STATUS_FAILED:
                    return 'ناموفق';
                    break;
                case self::STATUS_INQUIRY_PROBLEM:
                    return 'مغایرت بانکی';
                    break;
                default:
                    return 'نامشخص';
            }
        } elseif ($type == 'type') {
            switch ($label) {
                case self::TYPE_WEB_BASE and $type == 'type':
                    return 'اینترنتی';
                    break;
                case self::TYPE_CART_TO_CART and $type == 'type':
                    return 'کارت به کارت';
                    break;
                default:
                    return 'نامشخص';
            }
        }
    }
}
