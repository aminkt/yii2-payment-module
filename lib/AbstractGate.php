<?php

namespace aminkt\payment\lib;

use aminkt\payment\components\Payment;
use aminkt\payment\models\TransactionSession;
use yii\base\Component;
use yii\helpers\Inflector;

/**
 * Class AbstractGate
 * @package payment\lib
 */
abstract class AbstractGate extends Component
{
    /** @var string $pspName */
    public static $pspName = 'Gate';

    /** @var string $gateId */
    public static $gateId = 'G1';

    /** @var int Amount of transaction */
    protected $amount = 0;

    /** @var string  $callbackUrl */
    protected $callbackUrl;

    /** @var  string $orderId Order id */
    protected $orderId;

    /** @var  string $authority Payment authority */
    protected $authority;

    /** @var  string $trackingCode Payment tracking code */
    protected $trackingCode;

    /** @var  string $cardPan Payer card pan */
    protected $cardPan;

    /** @var  string $cardHash Payer card hash in sh2(uppercase($card)) */
    protected $cardHash;

    /** @var array $identityData */
    protected $identityData = [];


    /**
     * Dispatch payment response from bank.
     *
     * @return boolean
     */
    public abstract function dispatchRequest();

    /**
     * Prepare data and config gate for payment.
     *
     * @throws \aminkt\payment\exceptions\ConnectionException   Connection failed.
     *
     * @return array
     * Return data to redirect user to bank.
     */
    public abstract function payRequest();

    /**
     * Return an array that can be used to redirect user to bank gate way.
     *
     * Return format is like this:
     * <code>
     * [
     *  'action'=>'https://bank.shaparak.ir/payment
     *  'method'=>"POST",
     *  'inputs'=>[
     *      'amount'=>100,
     *      'merchant'=>123,
     *      ...
     *  ]
     * ]
     * </code>
     * Or use below definition to redirection:
     * <code>
     * [
     *      'redirect'=>'https://redirect.address
     * ]
     * </code>
     *
     * @return array
     */
    public abstract function redirectToBankFormData();

    /**
     * Verify Transaction if its paid. this method should call in callback from bank.
     *
     * @throws \aminkt\payment\exceptions\VerifyPaymentException
     * @throws \aminkt\payment\exceptions\ConnectionException
     *
     * @return AbstractGate|boolean
     */
    public abstract function verifyTransaction();

    /**
     * If for any reason you need check transaction status, this method ask again status of transaction from bank.
     * >**note: This method may not implement in all bank gates.**
     *
     * @throws \aminkt\payment\exceptions\ConnectionException
     * @throws \RuntimeException
     *
     * @return bool
     */
    public function inquiryTransaction(){
        return true;
    }

    /**
     * Return string system provider name.
     * @return string
     */
    public function getPSPName()
    {
        return static::$pspName;
    }


    /**
     * Get callback url.
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
        $this->callbackUrl = \Yii::$app->getUrlManager()->createAbsoluteUrl($callbackUrl);
        return $this;
    }

    /**
     * Return identity data.
     * @param $item
     * @return mixed
     */
    public function getIdentityData($item)
    {
        return $this->identityData[$item];
    }

    /**
     * Set identity data.
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
     * Return status of pay request, verify or inquiry request.
     * @return boolean
     */
    public abstract function getStatus();

    /**
     * @param int $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        if (is_int($amount) or is_float($amount) or is_double($amount) and $amount > 100)
            $this->amount = $amount;
        else
            throw new \InvalidArgumentException("Amount should be a numeric value and be grater than 100 in IR Toman");
        return $this;
    }

    /**
     * Return amount in IR Rial.
     * @param bool $rial Return amount as rial.
     * @return int
     */
    public function getAmount($rial = true)
    {
        if ($rial)
            return $this->amount * 10;

        return $this->amount;
    }

    /**
     * @return string
     */
    public function getAuthority()
    {
        return $this->authority;
    }

    /**
     * @param string $authority
     * @return $this
     */
    public function setAuthority($authority)
    {
        $this->authority = $authority;
        return $this;
    }

    /**
     * @return string
     */
    public function getTrackingCode()
    {
        return $this->trackingCode;
    }

    /**
     * @param string $trackingCode
     * @return $this
     */
    public function setTrackingCode($trackingCode)
    {
        $this->trackingCode = $trackingCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardPan()
    {
        return $this->cardPan;
    }

    /**
     * @param string $cardPan
     * @return $this
     */
    public function setCardPan($cardPan)
    {
        $this->cardPan = $cardPan;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardHash()
    {
        return $this->cardHash;
    }

    /**
     * @param string $cardHash
     * @return $this
     */
    public function setCardHash($cardHash)
    {
        $this->cardHash = $cardHash;
        return $this;
    }


    /**
     * Return order id.
     * @param bool $test Second value that define you should return test data. be care full that application should in test env to get test data.
     *
     * @return string
     */
    public function getOrderId($test = true)
    {
        if (YII_ENV_DEV and $test)
            return $this->orderId . '000';
        return $this->orderId;
    }

    /**
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        if (YII_ENV_DEV) {
            $orderId = str_replace('000', '', $orderId);
        }
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Magic method to handle some method that not implemented.
     *
     * @param string $name
     * @param array $params
     *
     * @return mixed
     */
    public function __call($name, $params)
    {
        if (method_exists($this, $name)) {
            return call_user_func_array([$this, $name], $params);
        } elseif (preg_match("/getIdentity(\w+)/", $name, $matches)) {
            return $this->getIdentityData(Inflector::variablize($matches[1]));
        }
        return parent::__call($name, $params);
    }


    /**
     * Return transaction model of current pay request.
     * This may return null in pay request.
     * Call this method after dispatch method.
     *
     * @return \aminkt\payment\models\TransactionSession|null
     */
    public function getTransactionModel()
    {
        $session = TransactionSession::findOne($this->getOrderId(false));
        if ($session)
            return $session;
        \Yii::warning("Transaction session model not found.");
        return null;
    }

}