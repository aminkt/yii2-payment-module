<?php

namespace aminkt\payment\lib;

use aminkt\payment\components\PaymentEvent;
use aminkt\payment\components\Payment;
use aminkt\payment\models\Transaction;
use yii\base\Component;
use yii\base\InvalidCallException;

/**
 * Class AbstractGate
 * @package payment\lib
 */
abstract class AbstractGate extends Component
{
    public $status;
    public $cardHolder;
    /** @var string  $factorNumber */
    protected $factorNumber;
    /** @var string  $transactionId */
    protected $transactionId;
    /** @var integer  $price */
    protected $price;
    /** @var string  $transTrackingCode */
    protected $transTrackingCode;
    /** @var string  $callbackUrl */
    protected $callbackUrl;
    /** @var array $identityData */
    protected $identityData = [];
    /** @var string  $transBankName */
    public static $transBankName = 'Gate';

    /** @var string $gateId */
    public static $gateId = 'G1';

    /** @var  Transaction $_transactionModel */
    protected $_transactionModel;


    /**
     * Prepare data and config gate for payment.
     * @return mixed
     */
    public function payRequest(){
        return true;
    }

    /**
     * Send user to bank page.
     * @return boolean
     */
    public abstract function sendToBank();

    /**
     * Verify Transaction if its paid. this method should call in callback from bank.
     * @return AbstractGate|boolean
     */
    public function verifyTransaction(){
        return true;
    }

    /**
     * If for any reason you need check transaction status, this method ask again status of transaction from bank.
     *
     * **note: This method may not implement in all bank gates.**
     * @return bool
     */
    public function inquiryTransaction(){
        return true;
    }

    /**
     * If your bank need your request to settle your money, this method send settle request to bank.
     *
     * **note: This method may not implement in all bank gates.**
     * @return boolean
     */
    public function settleTransaction(){
        return null;
    }

    /**
     * If for any reason you want return money to customer and cancel transaction, this method reverse money to customer card.
     *
     * **note: This method may not implement in all bank gates.**
     * @return boolean
     */
    public function reversalTransaction(){
        return null;
    }

    /**
     * Load gate data and return Transaction model of this gate.
     * @return Transaction
     */
    public function load(){
        try{
            $condition = [];
            if($this->transactionId){
                $condition['transId']=$this->transactionId;
            }elseif($this->factorNumber){
                $condition['factorNumber']=$this->factorNumber;
            }
            $transaction = static::getTransaction($condition);
            $this->_transactionModel = $transaction;
            if(!$transaction)
                return null;
            $this->setFactorNumber($transaction->factorNumber);
            $this->setTransactionId($transaction->transId);
            $this->setPrice($transaction->price);
            $this->setTransBankName($transaction->transBankName);
            $this->setTransTrackingCode($transaction->transTrackingCode);
            $this->setIdentityData($this->identityData);
            return $transaction;
        }catch (InvalidCallException $e){
            Payment::addError("Loading transaction failed");
            return null;
        }
    }


    /**
     * Create or update Transaction model
     * @return Transaction
     */
    public function transactionModel(){
        $transaction = $this->load();
        if(!$transaction){
            $transaction = new Transaction();
            $transaction->factorNumber = (string) $this->getFactorNumber();
            $transactionId = $this->getTransactionId();
            if($transactionId)
                $transaction->transId = (string)  $transactionId;
            else
                $transaction->transId = (string) $transaction->factorNumber;

            $transaction->price = $this->getPrice();
            $transaction->transBankName = $this->getTransBankName();
            $transaction->transTrackingCode = $this->getTransTrackingCode();
            $transaction->type = Transaction::TYPE_INTERNET_GATE;
            $transaction->ip = \Yii::$app->getRequest()->getUserIP();
        }
        return $transaction;
    }

    /**
     * By this static method you can all time load Transaction model.
     * @param array $condition
     * @return Transaction
     */
    public static function getTransaction($condition = []){
        $transaction = Transaction::findOne($condition);
        if($transaction)
            return $transaction;

        $condition = json_encode($condition);
        throw new InvalidCallException("Transaction model not found for transId = $condition");
    }


    /**
     * @return string
     */
    public function getFactorNumber()
    {
        return $this->factorNumber;
    }

    /**
     * @param string $factorNumber
     * @return $this
     */
    public function setFactorNumber($factorNumber)
    {
        $this->factorNumber = $factorNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     * @return $this
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getPrice()
    {

        return $this->price*10;
    }

    /**
     * @param int $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransBankName()
    {
        return static::$transBankName;
    }

    /**
     * @param string $transBankName
     * @return $this
     */
    public function setTransBankName($transBankName)
    {
        static::$transBankName = $transBankName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransTrackingCode()
    {
        return $this->transTrackingCode;
    }

    /**
     * @param string $transTrackingCode
     * @return $this
     */
    public function setTransTrackingCode($transTrackingCode)
    {
        $this->transTrackingCode = $transTrackingCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param array $callbackUrl
     * @return $this
     */
    public function setCallbackUrl($callbackUrl)
    {
        $bank = Payment::encryptBankName(static::$gateId);
        $token = Payment::generatePaymentToken();
        $callbackUrl['bc']=$bank;
        $callbackUrl['token']=$token;
//        $getData = [
//            'bc'=>$bank,
//            'token'=>$token,
//        ];
//        $callbackUrl = array_merge($callbackUrl, $getData);
        $this->callbackUrl = \Yii::$app->getUrlManager()->createAbsoluteUrl($callbackUrl);
        return $this;
    }

    /**
     * @param $item
     * @return array
     */
    public function getIdentityData($item)
    {
        return $this->identityData[$item];
    }

    /**
     * @param array $identityData
     * @return $this
     */
    public function setIdentityData($identityData)
    {
        $this->identityData = $identityData;
        return $this;
    }

    /**
     * Return bank requests as array.
     * @return mixed
     */
    public abstract function getRequest();

    /**
     * Return bank response as array.
     * @return mixed
     */
    public abstract function getResponse();

    /**
     * Return Response code of bank
     * @return string
     */
    public abstract function getResponseCode();
}