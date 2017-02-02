<?php

namespace payment\models;
use common\modules\payment\components\PaymentEvent;
use payment\components\Payment;
use payment\lib\AbstractGate;
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
    const STATUS_FAILED = 2;
    const STATUS_UNKNOWN = 0;
    const STATUS_INQUIRY_FAILED = 3;

    const EVENT_PAY_TRANSACTION = 'payTransaction';
    const EVENT_VERIFY_TRANSACTION = 'verifyTransaction';
    const EVENT_INQUIRY_TRANSACTION = 'inquiryTransaction';

    /** @var  $gate AbstractGate */
    public $gate;

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
     * @param $event PaymentEvent
     */
    public function payTransaction($event){
        $transactionModel = $event->gate->transactionModel();
        $transactionData = new TransactionData();
        $transactionLog = new TransactionLog();


        // Add new Transaction Log.
        $transactionLog->transId = $event->gate->getTransactionId();
        $transactionLog->bank = $transactionModel->transBankName;
        $transactionLog->status = $transactionLog::STATUS_PAY_REQUEST;
        $transactionLog->request = json_encode($event->gate->getRequest());
        $transactionLog->response = json_encode($event->gate->getResponse());
        $transactionLog->responseCode = $event->gate->getResponseCode();
        $transactionLog->ip = \Yii::$app->getRequest()->getUserIP();
        $transactionLog->time = time();
        $transactionLog->save(false);

        // Add new Transaction Data
        $transactionData->transactionId = $transactionModel->id;
        $transactionData->request = json_encode($event->gate->getRequest());
        $transactionData->response = null;
        $transactionData->responseStatus = $transactionData::RESPONSE_STATUS_UNKNOWN;
        $transactionData->responseTime = null;
        $transactionData->inquiryResponse = null;
        $transactionData->inquiryStatus = $transactionData::INQUIRY_STATUS_UNKNOWN;
        $transactionData->inquiryTime = null;
        $transactionData->save(false);

        // Save pay time.
        $transactionModel->status = $transactionModel::STATUS_UNKNOWN;
        $transactionModel->payTime = time();
        $transactionModel->save(false);
    }

    /**
     * This method invoke after verify of transaction event happen.
     * @param $event PaymentEvent
     */
    public function verifyTransaction($event){
        /** @var Transaction $transactionModel */
        $transactionModel = $event->gate->transactionModel();
        $transactionData = TransactionData::findOne(['transactionId'=>$transactionModel->id]);
        if(!$transactionData)
            Payment::addError("Transaction data not found", 22);
        $transactionLog = new TransactionLog();

        // Add new Cardholder
        if($event->gate->status){
            $cardHolder = new TransactionCardholder();
            $cardHolder->transactionId = $transactionModel->id;
            $cardHolder->createTime = time();
            $cardHolder->cardNumber = $event->gate->cardHolder;
            $cardHolder->save();
        }


        // Add new Transaction Log.
        $transactionLog->transId = $event->gate->getTransactionId();
        $transactionLog->bank = $transactionModel->transBankName;
        $transactionLog->status = $transactionLog::STATUS_VERIFY_REQUEST;
        $transactionLog->request = json_encode($event->gate->getRequest());
        $transactionLog->response = json_encode($event->gate->getResponse());
        $transactionLog->responseCode = $event->gate->getResponseCode();
        $transactionLog->ip = \Yii::$app->getRequest()->getUserIP();
        $transactionLog->time = time();
        $transactionLog->save(false);

        // Edit Transaction Data
        if($transactionData){
            $transactionData->response = json_encode($event->gate->getResponse());
            if($event->gate->status)
                $transactionData->responseStatus = $transactionData::RESPONSE_STATUS_OK;
            else
                $transactionData->responseStatus = $transactionData::RESPONSE_STATUS_FAILED;
            $transactionData->responseTime = time();
            $transactionData->save(false);
        }

        // Edit Transaction
        if($event->gate->status)
            $transactionModel->status = $transactionModel::STATUS_SUCCESS;
        else
            $transactionModel->status = $transactionModel::STATUS_FAILED;
        $transactionModel->save(false);
    }

    /**
     * This method invoke when inquiry transaction event happen.
     * @param $event
     */
    public function inquiryTransaction($event){
        /** @var Transaction $transactionModel */
        $transactionModel = $event->gate->transactionModel();
        $transactionData = TransactionData::findOne(['transactionId'=>$transactionModel->id]);
        if(!$transactionData)
            Payment::addError("Transaction data not found", 22);
        $transactionLog = new TransactionLog();

        // Add new Transaction Log.
        $transactionLog->transId = $event->gate->getTransactionId();
        $transactionLog->bank = $transactionModel->transBankName;
        $transactionLog->status = $transactionLog::STATUS_INQUIRY_REQUEST;
        $transactionLog->request = json_encode($event->gate->getRequest());
        $transactionLog->response = json_encode($event->gate->getResponse());
        $transactionLog->responseCode = $event->gate->getResponseCode();
        $transactionLog->ip = \Yii::$app->getRequest()->getUserIP();
        $transactionLog->time = time();
        $transactionLog->save(false);

        // Edit Transaction Data
        if($transactionData){
            $transactionData->inquiryResponse = json_encode($event->gate->getResponse());
            if($event->gate->status)
                $transactionData->inquiryStatus = $transactionData::INQUIRY_STATUS_OK;
            else
                $transactionData->inquiryStatus = $transactionData::INQUIRY_STATUS_FAILED;
            $transactionData->inquiryTime = time();
            $transactionData->save(false);
        }

        // Edit Transaction
        if(!$event->gate->status)
            $transactionModel->status = $transactionModel::STATUS_INQUIRY_FAILED;
        $transactionModel->save(false);
    }
}
