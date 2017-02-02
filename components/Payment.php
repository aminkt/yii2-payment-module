<?php
namespace payment\components;

use common\modules\payment\components\PaymentEvent;
use payment\lib\AbstractGate;
use payment\lib\PayirGate;
use payment\models\Transaction;
use payment\models\TransactionData;
use payment\models\TransactionLog;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\web\Cookie;

/**
 * Class Payment
 * @package payment\components
 */
class Payment extends Component{
    /** @var  string[] $gates */
    public $gates;
    /** @var array $callbackUrl array for show router of callback */
    public $callbackUr = ['/payment/default/verify'];

    /** @var AbstractGate[] $gatesObjects */
    protected static $gatesObjects = [];

    /** @var array $errors */
    protected static $errors = [];

    /** @var AbstractGate $currentGateObject */
    protected static $currentGateObject;


    /** @var  string $secureKey secure key for coding data. */
    private static $secureKey = 'tJ5WP0xVw3-rZABnoRHT';

    const SESSION_NAME_OF_TOKEN = 'payment_token_key';
    const SESSION_NAME_OF_BANK_POST_DATA = 'bank_post_data';
    const COOKIE_PAYMENT_BLOCKED = "payment_block_service";
    const COOKIE_PAYMENT_MUCH_ERROR = "payment_much_errors";


    public function init()
    {
        parent::init();
        if(is_array($this->gates) and count($this->gates)>0){
            foreach ($this->gates as $gate=>$config){
                $class = $config['class'];
                $identityData = $config['identityData'];
                /** @var AbstractGate $obj */
                $obj = new $class();
                $obj->setIdentityData($identityData);
                self::addGateObj($obj);
            }
        }else
            throw new InvalidCallException("Gates value is not correct.");
    }

    /**
     * Add Gate object to gate list
     * @param AbstractGate $gate
     */
    public static function addGateObj($gate){
        static::$gatesObjects[] = $gate;
    }

    /**
     * Send pay request to bank
     * @param integer $price
     * @param string $factorNumber
     * @return boolean
     */
    public function payRequest($price, $factorNumber){
        if(!static::isBlocked()){
            foreach (static::$gatesObjects as $gate){
                try{
                    $gate->setPrice($price)
                        ->setFactorNumber($factorNumber)
                        ->setCallbackUrl($this->callbackUr);
                    $payRequest = $gate->payRequest();
                    $this->initNewTransactionModel($gate);
                    if($payRequest){
                        $data = $gate->sendToBank();
                        \Yii::$app->getSession()->set(self::SESSION_NAME_OF_BANK_POST_DATA, json_encode($data));
                        \Yii::$app->response->redirect(['/payment/default/send'])->send();
                        self::$currentGateObject = $gate;
                        return true;
                    }else
                        throw new \RuntimeException();
                }catch (\Exception $e){
                    //$this->initNewTransactionModel($gate);
                    if($e->getMessage())
                        \Yii::error($e->getMessage(), self::className());

                    \Yii::error("Gate of ".$gate->getTransBankName()." not available now.", self::className());
                }
            }
        }else{
            static::addError("User blocked and services is not available right now.");
        }
        return false;
    }

    /**
     * Verify request
     * @return bool
     */
    public function verify(){
        if(!self::isBlocked()){
            $bankCode = \Yii::$app->getRequest()->get('bc');
            $token = \Yii::$app->getRequest()->get('token');
            if($bankName = self::validatePayment($token, $bankCode)){
                if (key_exists($bankName, $this->gates)){
                    $gateConfig = $this->gates[$bankName];
                    $class = $gateConfig['class'];
                    $identityData = $gateConfig['identityData'];
                    /** @var AbstractGate $gateObject */
                    $gateObject = new $class();
                    $gateObject->setIdentityData($identityData);
                    self::$currentGateObject = $gateObject;
                    $verify = $gateObject->verifyTransaction();
                    $this->saveVerifyTransactionModel($gateObject);
                    if($verify){
                        self::$currentGateObject = $gateObject;
                        return $verify;
                    }
                }
            }else{
                static::addError("Security error when try to tracking payment.");
            }
        }else{
            static::addError("User blocked and services is not available right now.");
        }
        return false;
    }


    /**
     * Inquiry request
     * @param string $bank name of gate
     * @param array $data
     * @return null
     */
    public function inquiry($bank, $data=[]){
        if(!self::isBlocked()){
            if (key_exists($bank, $this->gates)){
                $gateConfig = $this->gates[$bank];
                $class = $gateConfig['class'];
                $identityData = $gateConfig['identityData'];
                /** @var AbstractGate $gateObject */
                $gateObject = new $class();
                $gateObject->setIdentityData($identityData);

                if(key_exists('factorNumber', $data)){
                    $gateObject->setFactorNumber($data['factorNumber']);
                }
                if(key_exists('transactionId', $data)){
                    $gateObject->setTransactionId($data['transactionId']);
                }
                if(key_exists('transTrackingCode', $data)){
                    $gateObject->setTransTrackingCode($data['transTrackingCode']);
                }
                if(key_exists('price', $data)){
                    $gateObject->setPrice($data['price']);
                }
                self::$currentGateObject = $gateObject;
                $inquiry = $gateObject->inquiryTransaction();
                $this->saveInquiryTransactionModel($gateObject);
                if($inquiry){
                    self::$currentGateObject = $gateObject;
                    return $inquiry;
                }
            }
        }else{
            static::addError("User blocked and services is not available right now.");
        }
        return false;
    }


    /**
     * Save transaction data in db when pay request send and return true if its work correctly.
     * @param $gate AbstractGate
     * @return bool
     */
    public function initNewTransactionModel($gate){
        $transaction = $gate->transactionModel();
        $transaction->status = $transaction::STATUS_UNKNOWN;
        if($transaction->save()){
            $event = new PaymentEvent();
            $event->gate = $gate;
            $event->time = time();
            $transaction->trigger(Transaction::EVENT_PAY_TRANSACTION, $event);
            return true;
        }else{
            \Yii::error($transaction->getErrors(), self::className());
            Payment::addError("Saving transaction model failed");
        }
        return false;
    }

    /**
     * Save transaction data in db when verify request send and return true if its work correctly.
     * @param $gate AbstractGate
     * @return bool
     */
    public function saveVerifyTransactionModel($gate){
        $event = new PaymentEvent();
        $event->gate = $gate;
        $event->time = time();
        $gate->transactionModel()->trigger(Transaction::EVENT_VERIFY_TRANSACTION, $event);
        return true;
    }

    /**
     * Save transaction data in db when inquiry request send and return true if its work correctly.
     * @param $gate AbstractGate
     * @return bool
     */
    public function saveInquiryTransactionModel($gate){
        $event = new PaymentEvent();
        $event->gate = $gate;
        $event->time = time();
        $gate->transactionModel()->trigger(Transaction::EVENT_INQUIRY_TRANSACTION, $event);
        return true;
    }


    /**
     * @return AbstractGate
     */
    public static function getCurrentGateObject()
    {
        return self::$currentGateObject;
    }


    /**
    * @return string
    */
    public static function getSecureKey()
    {
        return self::$secureKey;
    }


    /**
     * Generate and set payment token.
     * @return string
     */
    public static function generatePaymentToken(){
        $token = \Yii::$app->getSecurity()->generateRandomString(10);
        \Yii::$app->getSession()->set(self::SESSION_NAME_OF_TOKEN, $token);
        return $token;
    }

    /**
     * Validate payment
     * @param $paymentToken
     * @param $bankCode
     * @return bool|string
     */
    public static function validatePayment($paymentToken, $bankCode){
        if($bankCode){
            $bankName = self::decryptBankName($bankCode);
            if($bankName == PayirGate::$gateId)
                return $bankName;

            if(self::validatePaymentToken($paymentToken))
                return $bankName;
        }
        return false;
    }

    /**
     * Validate if prepared token is valid or not.
     * @param string $paymentToken
     * @return bool
     */
    public static function validatePaymentToken($paymentToken){
        $token = \Yii::$app->getSession()->get(self::SESSION_NAME_OF_TOKEN);
        $token = trim($token);
        $paymentToken = trim($paymentToken);
        if($token == $paymentToken)
            return true;
        return false;
    }

    /**
     * Encrypt bank name to define correct gate in callback.
     * @param $bankName
     * @return string
     */
    public static function encryptBankName($bankName){
        return $bankName;
        return \Yii::$app->getSecurity()->encryptByKey($bankName, Payment::getSecureKey());
    }

    /**
     * Decrypt bank name in callback to select correct gate.
     * @param $bankCode
     * @return bool|string
     */
    public static function decryptBankName($bankCode){
        return $bankCode;
        $bankName = \Yii::$app->getSecurity()->decryptByKey($bankCode, Payment::getSecureKey());
        return $bankName;
    }

    /**
     * Check if customer is blocked because of bad behaviors or not.
     * @return bool
     */
    public static function isBlocked(){
        $cookiesRequest = \Yii::$app->request->cookies;
        if($cookiesRequest->has(static::COOKIE_PAYMENT_BLOCKED)){
            return true;
        }
        if(static::getBlockCounter() >= 5){
            static::blockPaymentServices();
            return true;
        }
        return false;
    }

    /**
     * Add an error to error collection/
     * @param $message string
     * @param int|string $code string
     * @param bool $countUpBlockCounter
     */
    public static function addError($message, $code=0, $countUpBlockCounter = false){
        \Yii::error($message, $code);
        static::$errors[] = [
            'message'=>$message,
            'code'=>$code,
        ];
        if($countUpBlockCounter){
            static::incrementBlockCounter();
        }
    }

    /**
     * Clean errors collection
     * @param bool $resetBlockCounter
     */
    public static function cleanErrors($resetBlockCounter=false){
        static::$errors = [];
        if($resetBlockCounter){
            static::resetBlockCounter();
        }
    }

    /**
     * Return errors as array
     * @return array
     */
    public static function getErrors(){
        return static::$errors;
    }

    /**
     * Return counter of block.
     * @return integer
     */
    public static function getBlockCounter(){
        $cookiesRequest = \Yii::$app->request->cookies;
        return $cookiesRequest->getValue(static::COOKIE_PAYMENT_MUCH_ERROR, 0);
    }

    /**
     * Increment counter of block.
     */
    public static function incrementBlockCounter(){
        $cookiesResponse = \Yii::$app->response->cookies;
        $cookiesRequest = \Yii::$app->request->cookies;
        $counter = $cookiesRequest->getValue(static::COOKIE_PAYMENT_MUCH_ERROR, 0);
        $counter++;
        $cookiesResponse->add(new Cookie([
            'name'=>static::COOKIE_PAYMENT_MUCH_ERROR,
            'value'=>$counter,
            'expire'=>time()+60*60*3
        ]));
    }

    /**
     * Reset counter of block.
     */
    public static function resetBlockCounter(){
        $cookiesResponse = \Yii::$app->response->cookies;
        $cookiesRequest = \Yii::$app->request->cookies;
        if($cookiesRequest->has(static::COOKIE_PAYMENT_MUCH_ERROR)){
            $cookiesResponse->add(new Cookie([
                'name'=>static::COOKIE_PAYMENT_MUCH_ERROR,
                'value'=>0,
                'expire'=>time()+60*60*3
            ]));
        }
    }

    /**
     * Block user
     */
    public static function blockPaymentServices(){
        $cookiesResponse = \Yii::$app->response->cookies;
        $cookiesResponse->add(new Cookie([
            'name'=>static::COOKIE_PAYMENT_BLOCKED,
            'value'=>static::getBlockCounter(),
            'expire'=>time()+60*60*24
        ]));
    }
}