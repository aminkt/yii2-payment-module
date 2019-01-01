<?php

namespace aminkt\yii2\payment\components;

use aminkt\exceptions\RequestPaymentException;
use aminkt\exceptions\ConnectionException;
use aminkt\exceptions\SecurityException;
use aminkt\exceptions\VerifyPaymentException;
use aminkt\yii2\payment\gates\AbstractGate;
use aminkt\yii2\payment\InvalidAmountException;
use aminkt\yii2\payment\models\TransactionSession;
use aminkt\yii2\payment\traits\LogTrait;
use aminkt\yii2\payment\traits\SecurityTrait;
use yii\base\Component;
use yii\base\InvalidCallException;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

/**
 * Class Payment
 *
 * Define payment component that implement a clean interface to payment action.
 *
 * @package payment\components
 */
class Payment extends Component
{
    use SecurityTrait;
    use LogTrait;

    const CACHE_PAYMENT_BLOCKED = "payment.block.user";
    const CACHE_PAYMENT_BLOCK_ERRORS_COUNT = "payment.block.errors.count";

    const CACHE_LOC_VERIFY_PROCESS = "payment.verify.locking";

    const ERROR_ALL_GATES_IS_BUSY = 1;
    const ERROR_USER_BLOCKED = 2;


    /**
     * Security key used to encrypt data.
     *
     * @var  string $encrypttKey secure key for coding data.
     */
    public $encryptKey = null;

    /**
     * List of avilable gates.
     *
     * @var  array $gates
     */
    public $gates;

    /**
     * Time that system can validate bank response. Default is null. Set time in second that if user returned from bank
     * then system accept the response. If user do not return from bank in defined time then response will not accept.
     *
     * @var integer $bankTimeout
     */
    public $bankTimeout = null;

    /**
     * Block time. this value define if a user blocked how much should prevent action from that.
     * If set null for ever considered.
     * Value should be in second. default value is 86400 mean 1 day.
     *
     * @var integer
     */
    public $blockTime = 86400;

    /**
     * Max allowed times that a user can has credential errors.
     * Default value is 5 times. after that user will block.
     * @var int
     */
    public $allowedCredentialErrors = 5;

    /**
     * Define callback address.
     * This address used to redirect user to that when bank redirect user to site.
     *
     * @var array $callbackUrl array for show router of callback
     */
    public $callback = ['/payment/default/verify'];

    /**
     * Enable by pass.
     *
     * @var bool
     */
    protected $enableByPass = false;


    /**
     * @inheritdoc
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function init()
    {
        parent::init();

        if (!is_array($this->gates) and !(count($this->gates) > 0)) {
            throw new InvalidCallException("Gates value is not correct.");
        }

        if (!is_array($this->callback) and !(count($this->callback) > 0)) {
            throw new InvalidConfigException("Callback is required.");
        }

        $this->enableByPass = \aminkt\yii2\payment\Payment::getInstance()->enableByPass();
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
     * @param \aminkt\yii2\payment\interfaces\OrderInterface $order Order model object.
     * @param string                                         $description
     *
     * @throws \Exception
     *
     * @return bool Return false if request become failed or Return an array that can be used to redirect user to
     *                    bank gate way.
     */
    public function payRequest($order, $description = null)
    {
        if (!static::isBlocked()) {
            foreach ($this->gates as $name => $gate) {
                try {
                    $this->callback['token'] = $this->generateToken($name);

                    $gateObj = static::initGateObject($gate)
                        ->setAmount($order->getPayAmount())
                        ->setCallbackUrl($this->callback);


                    // Save session data.
                    $session = $this->savePaymentDataIntoDatabase($gateObj, $order, $description);

                    $gateObj->setOrderId($session->id);

                    if(!$this->enableByPass) {
                        $gateObj->connect()->payRequest();
                    }

                    if ($this->enableByPass or $gateObj->getStatus()) {

                        if ($gateObj->getAuthority()) {
                            $this->updatePaymentDataInDatabase($session, 'authority', $gateObj->getAuthority());
                        }

                        if($this->enableByPass) {
                            $data = [
                                'action' => $gateObj->getCallbackUrl(),
                                'method' => 'POST',
                                'inputs' => [
                                    'amount' => $gateObj->getAmount(),
                                    'orderId' => $gateObj->getOrderId(),
                                    'byPassReq' => true,
                                ]
                            ];
                        } else {
                            $data = $gateObj->redirectToBankFormData();
                        }

                        $this->redirect($data['action'], $data['inputs'], $data['method']);
                        die();
                    } else {
                        throw new RequestPaymentException("Problem in payment request.");
                    }

                } catch (ConnectionException $exception) {
                    \Yii::error("Gate not available now.", self::className());
                    \Yii::error($exception->getMessage(), self::className());
                    \Yii::error($exception->getTrace(), self::className());
                }
            }

            static::addError("Can not connect to bank gates. All gates are into problem.", static::ERROR_ALL_GATES_IS_BUSY);
        } else {
            static::addError("User blocked and services is not available right now.", static::ERROR_USER_BLOCKED);
        }

        return false;
    }

    /**
     * Initilize a gate based ob gate object.
     *
     * @param array $config Gate config.
     *
     * @return \aminkt\yii2\payment\gates\AbstractGate
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public static function initGateObject($config)
    {
        $class = $config['class'];
        $identityData = $config['identityData'];
        /** @var \aminkt\yii2\payment\gates\AbstractGate $obj */
        $obj = new $class();
        $obj->setIdentityData($identityData);
        return $obj;
    }

    /**
     * Generate a form and redirect user emidetly to bank gate.
     *
     * @param        $url
     * @param        $data
     * @param string $mthod
     *
     * @return void
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    protected function redirect($url, $data, $mthod = 'post')
    {
        header("Content-Type: text/html");
        $id = time();
        echo Html::beginForm($url, $mthod, ['id' => $id]);
        foreach ($data as $key => $value) {
            echo Html::hiddenInput($key, $value);
        }
        echo Html::endForm();

        echo <<<HTML
<script>
let form = document.getElementById("{$id}");
form.submit();
</script>
HTML;
        die();

    }

    /**
     * Verify payment request and update database variables.
     * This method will handle man in the midle attack too.
     *
     * @throws \Exception
     *
     * @return AbstractGate|bool
     */
    public function verify()
    {
        if (!self::isBlocked()) {
            $token = \Yii::$app->getRequest()->get('token');
            if ($gate = $this->decryptToken($token)) {
                if (key_exists($gate, $this->gates)) {
                    $gateConfig = $this->gates[$gate];
                    try {
                        $gateObject = static::initGateObject($gateConfig);

                        $gateObject->dispatchRequest();

                        $session = $gateObject->getTransactionModel();

                        if (!$session) {
                            throw new NotFoundHttpException("Session not found.");
                        }
                        if ($session->status == $session::STATUS_PAID) {
                            throw new SecurityException("This order paid before.");
                        }

                        $gateObject->setAmount($session->amount);

                        $locVerifyCacheName = self::CACHE_LOC_VERIFY_PROCESS . '.' . $gateObject->getOrderId();
                        while (\Yii::$app->getCache()->exists($locVerifyCacheName) and !YII_ENV_DEV) {
                            // Wait for running verify request.
                        }

                        \Yii::$app->getCache()->set($locVerifyCacheName, true);

                        if(!$this->enableByPass) {
                            $gateObject->verifyTransaction();
                        }

                        $this->saveVerifyDataIntoDatabase($gateObject);
                        if ($this->enableByPass or $gateObject->getStatus()) {
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                            return true;
                        }
                        \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (\aminkt\exceptions\SecurityException $exception) {
                        \Yii::error("Try to double spending");
                        throw $exception;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Inquiry request
     *
     * @param \aminkt\yii2\payment\models\TransactionInquiry $transactionInquiry
     *
     * @throws \Exception
     *
     * @return null
     */
    public function inquiry($transactionInquiry)
    {
        throw new \RuntimeException("This class implemention is not done yet");
        try {
            $transactionSession = $transactionInquiry->transactionSession;
            /** @var AbstractGate $gateObject */
            $gateClassName = $transactionSession->psp;
            $gateObject = new $gateClassName();
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
}