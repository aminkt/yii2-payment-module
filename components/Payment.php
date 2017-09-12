<?php

namespace aminkt\payment\components;

use aminkt\payment\exceptions\ConnectionException;
use aminkt\payment\exceptions\SecurityException;
use aminkt\payment\exceptions\VerifyPaymentException;
use aminkt\payment\lib\AbstractGate;
use aminkt\payment\lib\PayirGate;
use aminkt\payment\models\Transaction;
use aminkt\payment\models\TransactionData;
use aminkt\payment\models\TransactionInquiry;
use aminkt\payment\models\TransactionLog;
use aminkt\payment\models\TransactionSession;
use aminkt\userAccounting\exceptions\RuntimeException;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\helpers\Html;
use yii\web\Cookie;
use yii\web\NotFoundHttpException;

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
    const CACHE_LOC_VERIFY_PROCESS = "verify_process_locking";


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
     *
     * Return false if request become failed or Return an array that can be used to redirect user to bank gate way.
     *
     * Return format is like this:
     *
     * [
     *     'action'=>'https://bank.shaparak.ir/payment
     *     'method'=>"POST",
     *     'inputs'=>[
     *         'amount'=>100,
     *         'merchant'=>123,
     *         ...
     *     ]
     * ]
     *
     * @param integer $amount Amount in IR TOMAN.
     * @param string $orderId
     * @param string $description
     *
     * @throws \Exception
     *
     * @return array|bool Return false if request become failed or Return an array that can be used to redirect user to bank gate way.
     */
    public function payRequest($amount, $orderId, $description = null)
    {
        if(!static::isBlocked()){
            foreach (static::$gatesObjects as $gate){
                try{
                    self::$currentGateObject = $gate;
                    self::$currentGateObject->setAmount($amount)
                        ->setCallbackUrl($this->callbackUr);
                    $payRequest = self::$currentGateObject->payRequest();

                    $sessionId = $this->savePaymentDataIntoDatabase(self::$currentGateObject, $orderId, $description);
                    self::$currentGateObject->setOrderId($sessionId);

                    if($payRequest){
                        $data = self::$currentGateObject->redirectToBankFormData();
                        \Yii::$app->getSession()->set(self::SESSION_NAME_OF_BANK_POST_DATA, json_encode($data));
                        \Yii::$app->response->redirect(['/payment/default/send'])->send();
                        return $data;
                    }else
                        throw new \RuntimeException();

                } catch (ConnectionException $exception) {
                    \Yii::error("Gate of " . $gate->getPSPName() . " not available now.", self::className());
                    \Yii::error($exception->getMessage(), self::className());
                    \Yii::error($exception->getTrace(), self::className());
                } catch (\RuntimeException $exception) {
                    \Yii::error("Gate of " . $gate->getPSPName() . " has problem in payment request.", self::className());
                    \Yii::error($exception->getMessage(), self::className());
                    \Yii::error($exception->getTrace(), self::className());
                } catch (\Exception $exception) {
                    \Yii::error("Gate of " . $gate->getPSPName() . " has a hard error while trying to send payment request.", self::className());
                    \Yii::error($exception->getMessage(), self::className());
                    \Yii::error($exception->getTrace(), self::className());
                    throw $exception;
                }
            }

            static::addError("Can not connect to bank gates. All gates are into problem.", 1);
        }else{
            static::addError("User blocked and services is not available right now.", 112);
        }
        return false;
    }

    /**
     * Verify request
     *
     * @throws \Exception
     *
     * @return AbstractGate|bool
     */
    public function verify(){
        if(!self::isBlocked()){
            $bankCode = \Yii::$app->getRequest()->get('bc');
            $token = \Yii::$app->getRequest()->get('token');
            if($bankName = self::validatePayment($token, $bankCode)){
                if (key_exists($bankName, $this->gates)){
                    $gateConfig = $this->gates[$bankName];
                    try {
                        /** @var AbstractGate $gateObject */
                        $gateObject = new $gateConfig['class']();
                        $gateObject->setIdentityData($gateConfig['identityData']);
                        self::$currentGateObject = $gateObject;

                        self::$currentGateObject->dispatchRequest();
                        $session = TransactionSession::findOne(['authority' => self::$currentGateObject->getAuthority()]);
                        if (!$session) {
                            throw new NotFoundHttpException("Session not found.");
                        }
                        self::$currentGateObject->setAmount($session->amount)
                            ->setOrderId($session->id);

                        $locVerifyCacheName = self::CACHE_LOC_VERIFY_PROCESS . '.' . self::$currentGateObject->getOrderId();
                        while (\Yii::$app->getCache()->exists($locVerifyCacheName)) {
                            // Wait for running verify request.
                        }

                        \Yii::$app->getCache()->set($locVerifyCacheName, true);
                        $verify = self::$currentGateObject->verifyTransaction();
                        $this->saveVerifyDataIntoDatabase(self::$currentGateObject);
                        if ($verify) {
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                            return $verify;
                        }
                        \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (NotFoundHttpException $exception) {
                        \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " verify become failed.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        throw $exception;
                    } catch (VerifyPaymentException $exception) {
                        \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " verify become failed.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (SecurityException $exception) {
                        \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " have security error.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (ConnectionException $exception) {
                        \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " not available now.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (\RuntimeException $exception) {
                        \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " has problem in verify payment.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (\Exception $exception) {
                        \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " has a hard error while trying to verify payment request.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                        throw $exception;
                    }
                } else {
                    static::addError("Security error when try to tracking payment.\nDefined PSP is not valid.", 111, true);
                }
            } else {
                static::addError("Security error when try to tracking payment.", 111, true);
            }
        } else {
            static::addError("User blocked and services is not available right now.", 112);
        }
        return false;
    }


    /**
     * Inquiry request
     *
     * @param TransactionInquiry $transactionInquiry
     *
     * @throws \Exception
     *
     * @return null
     */
    public function inquiry($transactionInquiry)
    {
        try {
            $transactionSession = $transactionInquiry->transactionSession;
            /** @var AbstractGate $gateObject */
            $gateObject = new $transactionSession->psp();
            $gateConfig = $this->gates[$gateObject::$gateId];
            $gateObject->setIdentityData($gateConfig['identityData']);

            $gateObject->setOrderId($transactionSession->id)
                ->setAuthority($transactionSession->authority)
                ->setTrackingCode($transactionSession->trackingCode)
                ->setAmount($transactionSession->amount);

            self::$currentGateObject = $gateObject;
            $inquiry = self::$currentGateObject->inquiryTransaction();
            $this->saveInquiryDataIntoDatabase(self::$currentGateObject, $transactionInquiry);
            if ($inquiry) {
                return $inquiry;
            }
        } catch (ConnectionException $exception) {
            \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " not available now.", self::className());
            \Yii::error($exception->getMessage(), self::className());
            \Yii::error($exception->getTrace(), self::className());
        } catch (\RuntimeException $exception) {
            \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " has problem in inquiry payment.", self::className());
            \Yii::error($exception->getMessage(), self::className());
            \Yii::error($exception->getTrace(), self::className());
        } catch (\Exception $exception) {
            \Yii::error("Gate " . self::$currentGateObject->getPSPName() . " has a hard error while trying to inquiry payment request.", self::className());
            \Yii::error($exception->getMessage(), self::className());
            \Yii::error($exception->getTrace(), self::className());
            throw $exception;
        }
        return false;
    }


    /**
     * Save payment data in db when pay request send and return true if its work correctly.
     *
     * @param AbstractGate $gate
     * @param string $orderId
     * @param string $description
     * @return string Return false if not saved into database and if saving was successful return primary key value.
     */
    public function savePaymentDataIntoDatabase($gate, $orderId, $description)
    {
        // Create transaction session data.
        $transactionSession = new TransactionSession();
        $transactionSession->authority = $gate->getAuthority();
        $transactionSession->orderId = $orderId;
        $transactionSession->psp = $gate::className();
        $transactionSession->amount = $gate->getAmount();
        $transactionSession->description = Html::encode($description);
        $transactionSession->status = TransactionSession::STATUS_NOT_PAID;
        $transactionSession->type = TransactionSession::TYPE_WEB_BASE;
        $transactionSession->ip = \Yii::$app->getRequest()->getUserIP();
        if ($transactionSession->save()) {
            /**
             * Set transaction session id as payment order id.
             * Actual order id can be access from database later.
             **/
            $gate->setOrderId($transactionSession->id);

            /**
             * Save transactions logs.
             */
            self::saveLogData($transactionSession, $gate, TransactionLog::STATUS_PAYMENT_REQ);

            /**
             * Throw an verify event. can be used in kernel to save and modify transactions.
             */
            $transactionSession = TransactionSession::findOne($gate->getOrderId());
            $event = new PaymentEvent();
            $event->setGate($gate)
                ->setStatus($gate->getStatus())
                ->setTransactionSession($transactionSession);
            $this->trigger(\aminkt\payment\Payment::EVENT_PAYMENT_REQUEST, $event);

            return $transactionSession->id;
        }

        \Yii::error($transactionSession->getErrors(), self::className());
        throw new \InvalidArgumentException("Can not saving data into database.", 10);
    }

    /**
     * Save transaction data in db when verify request send and return true if its work correctly.
     *
     * @param $gate AbstractGate
     *
     * @throws \aminkt\payment\exceptions\SecurityException
     *
     * @return bool
     */
    public function saveVerifyDataIntoDatabase($gate)
    {
        /**
         * Throw an verify event. can be used in kernel to save and modify transactions.
         */
        $transactionSession = TransactionSession::findOne($gate->getOrderId());

        /**
         * Save transactions logs.
         */
        self::saveLogData($transactionSession, $gate, TransactionLog::STATUS_PAYMENT_VERIFY);

        /**
         * Check transaction correctness.
         */
        if ($transactionSession->status == TransactionSession::STATUS_PAID) {
            throw new SecurityException("This transaction paid before.");
            // or return false and track in verify method.
            return false;
        }

        /**
         * Update transactionSession data.
         */
        $transactionSession->userCardHash = $gate->getCardHash();
        $transactionSession->userCardPan = $gate->getCardPan();
        $transactionSession->trackingCode = $gate->getTrackingCode();
        if ($gate->getStatus()) {
            $transactionSession->status = TransactionSession::STATUS_PAID;
        } else {
            $transactionSession->status = TransactionSession::STATUS_FAILED;
        }

        if (!$transactionSession->save()) {
            \Yii::error($transactionSession->getErrors(), self::className());
            throw new RuntimeException('Can not save transaction session data.', 12);
        } else {
            /**
             * Create an inquiry request for valid payments.
             */
            $inquiryRequest = new TransactionInquiry();
            $inquiryRequest->sessionId = $transactionSession->id;
            $inquiryRequest->status = TransactionInquiry::STATUS_INQUIRY_WAITING;
            $inquiryRequest->save(false);
        }

        /**
         * Throw an verify event. can be used in kernel to save and modify transactions.
         */
        $event = new PaymentEvent();
        $event->setGate($gate)
            ->setStatus($gate->getStatus())
            ->setTransactionSession($transactionSession);
        $this->trigger(\aminkt\payment\Payment::EVENT_PAYMENT_VERIFY, $event);
        return true;
    }

    /**
     * Save transaction data in db when inquiry request send and return true if its work correctly.
     *
     * @param AbstractGate $gate
     * @param TransactionInquiry $inquiryModel
     *
     * @return bool
     */
    public function saveInquiryDataIntoDatabase($gate, $inquiryModel)
    {
        /**
         * Save transactions logs.
         */
        self::saveLogData($inquiryModel->transactionSession, $gate, TransactionLog::STATUS_PAYMENT_INQUIRY);

        if ($gate->getStatus()) {
            $inquiryModel->status = TransactionInquiry::STATUS_INQUIRY_SUCCESS;
        } else {
            $inquiryModel->status = TransactionInquiry::STATUS_INQUIRY_FAILED;
        }

        if (!$inquiryModel->save()) {
            \Yii::error($inquiryModel->getErrors(), self::className());
            throw new RuntimeException('Can not save transaction inquiry data.', 12);
        }

        /**
         * Throw an verify event. can be used in kernel to save and modify transactions.
         */
        $event = new PaymentEvent();
        $event->setGate($gate)
            ->setStatus($gate->getStatus())
            ->setTransactionInquiry($inquiryModel)
            ->setTransactionSession($inquiryModel->transactionSession);
        $this->trigger(\aminkt\payment\Payment::EVENT_PAYMENT_INQUIRY, $event);
        return true;
    }

    /**
     * Save transactions logs.
     *
     * @param \aminkt\payment\models\TransactionSession $transactionSession
     * @param \aminkt\payment\lib\AbstractGate $gate
     * @param string $status
     *
     * @return void
     */
    public static function saveLogData($transactionSession, $gate, $status = TransactionLog::STATUS_UNKNOWN)
    {
        $log = new TransactionLog();
        $log->sessionId = $transactionSession->id;
        $log->bankDriver = $gate::className();
        $log->status = $status;
        $log->request = json_encode($gate->getRequest());
        $log->response = json_encode($gate->getResponse());
        $log->ip = \Yii::$app->getRequest()->getUserIP();
        $log->save(false);
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
    }

    /**
     * Decrypt bank name in callback to select correct gate.
     * @param $bankCode
     * @return bool|string
     */
    public static function decryptBankName($bankCode){
        return $bankCode;
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