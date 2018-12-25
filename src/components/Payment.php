<?php

namespace aminkt\yii2\payment\components;

use aminkt\exceptions\RequestPaymentException;
use aminkt\yii2\payment\exceptions\ConnectionException;
use aminkt\yii2\payment\exceptions\SecurityException;
use aminkt\yii2\payment\exceptions\VerifyPaymentException;
use aminkt\yii2\payment\gates\AbstractGate;
use aminkt\yii2\payment\InvalidAmountException;
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

    const COOKIE_PAYMENT_BLOCKED = "payment_block_service";
    const COOKIE_PAYMENT_MUCH_ERROR = "payment_much_errors";

    const CACHE_LOC_VERIFY_PROCESS = "verify_process_locking";

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
     * @var  string[] $gates
     */
    public $gates;

    /**
     * Time that system can validate bank response. Default is null. Set time in second that if user returned from bank
     * then system accept the response. If user do not return from bank in defined time then reponse will not accept.
     *
     * @var integer $bankTimeout
     */
    public $bankTimeout;

    /**
     * Define callback address.
     * This address used to redirect user to that when bank redirect user to site.
     *
     * @var array $callbackUrl array for show router of callback
     */
    public $callback = ['/payment/default/verify'];


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

        if(!is_array($this->callback) and !(count($this->callback) > 0)) {
            throw new InvalidConfigException("Callback is required.");
        }

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
     * @return array|bool Return false if request become failed or Return an array that can be used to redirect user to
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
                    $session = $this->savePaymentDataIntoDatabase($gateObj, $order->getId(), $description);

                    $gateObj->setOrderId($session->id);

                    if ($payRequest = $gateObj->payRequest()) {

                        if ($payRequest->getAuthority()) {
                            $this->updatePaymentDataInDatabase($session, 'authority', $gateObj->getAuthority());
                        }

                        $data = $gateObj->redirectToBankFormData();

                        return $this->redirect($data['action'], $data['inputs'], $data['method']);
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
     * @return AbstractGate
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public static function initGateObject($config)
    {
        $class = $config['class'];
        $identityData = $config['identityData'];
        /** @var AbstractGate $obj */
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
                        self::$currentGateObject->setAmount($session->amount);

                        $locVerifyCacheName = self::CACHE_LOC_VERIFY_PROCESS . '.' . self::$currentGateObject->getOrderId(false);
                        while (\Yii::$app->getCache()->exists($locVerifyCacheName) and !YII_ENV_DEV) {
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
                        \Yii::error("Gate verify become failed.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        throw $exception;
                    } catch (VerifyPaymentException $exception) {
                        \Yii::error("Gate verify become failed.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (SecurityException $exception) {
                        \Yii::error("Gate have security error.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (ConnectionException $exception) {
                        \Yii::error("Gate not available now.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (\RuntimeException $exception) {
                        \Yii::error("Gate has problem in verify payment.", self::className());
                        \Yii::error($exception->getMessage(), self::className());
                        \Yii::error($exception->getTrace(), self::className());
                        if (isset($locVerifyCacheName))
                            \Yii::$app->getCache()->delete($locVerifyCacheName);
                    } catch (\Exception $exception) {
                        \Yii::error("Gate has a hard error while trying to verify payment request.", self::className());
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
}