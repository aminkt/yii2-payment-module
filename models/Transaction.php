<?php

namespace payment\models;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "{{%transaction}}".
 *
 * @property integer $id
 * @property string $factorNumber
 * @property string $transId
 * @property integer $price
 * @property string $transBankName
 * @property string $transTrackingCode
 * @property integer $type
 * @property integer $status
 * @property string $ip
 * @property integer $payTime
 * @property integer $createTime
 *
 * @property TransactionCardholder $transactionCardholder
 * @property TransactionData $transactionData
 * @property TransactionLog[] $transactionLogs
 */
class Transaction extends \yii\db\ActiveRecord
{
    const TYPE_INTERNET_GATE = 1;
    const TYPE_CART_TO_CART = 2;
    const TYPE_USSD = 3;
    const TYPE_CASH = 4;

    const STATUS_SUCCESS = 1;
    const STATUS_FAIL = 2;
    const STATUS_UNKNOWN = 0;
    const STATUS_INQUIRY_FAIL = 3;

    const EVENT_PAY_TRANSACTION = 'payTransaction';
    const EVENT_VERIFY_TRANSACTION = 'verifyTransaction';
    const EVENT_INQUIRY_TRANSACTION = 'inquiryTransaction';

    public function init()
    {
        parent::init();
        $this->on(self::EVENT_PAY_TRANSACTION, [$this, 'payTransaction']);
        $this->on(self::EVENT_INQUIRY_TRANSACTION, [$this, 'inquiryTransaction']);
        $this->on(self::EVENT_VERIFY_TRANSACTION, [$this, 'verifyTransaction']);
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['createTime'],
                ],
                // if you're using datetime instead of UNIX timestamp:
                // 'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%transaction}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['factorNumber', 'type', 'status', 'price'], 'required'],
            [['price', 'type', 'status', 'payTime', 'createTime'], 'integer'],
            [['factorNumber', 'transId', 'transBankName', 'transTrackingCode', 'ip'], 'string', 'max' => 255],
            [['factorNumber'], 'unique'],
            [['transId'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'factorNumber' => 'Factor Number',
            'transId' => 'Trans ID',
            'price' => 'Price',
            'transBankName' => 'Trans Bank Name',
            'transTrackingCode' => 'Trans Tracking Code',
            'type' => 'Type',
            'status' => 'Status',
            'ip' => 'Ip',
            'payTime' => 'Pay Time',
            'createTime' => 'Create Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransactionCardholder()
    {
        return $this->hasOne(TransactionCardholder::className(), ['transactionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransactionData()
    {
        return $this->hasOne(TransactionData::className(), ['transactionId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransactionLogs()
    {
        return $this->hasMany(TransactionLog::className(), ['transId' => 'transId']);
    }

    /**
     * This method invoke after pay event happen.
     * @param $event
     */
    public function payTransaction($event){
        $this->payTime = time();
        $this->save(false);
    }

    /**
     * This method invoke after verify of transaction event happen.
     * @param $event
     */
    public function verifyTransaction($event){

    }

    /**
     * This method invoke when inquiry transaction event happen.
     * @param $event
     */
    public function inquiryTransaction($event){

    }
}
