<?php

namespace aminkt\yii2\payment\gates;

use aminkt\exceptions\InvalidAmountException;
use aminkt\exceptions\NotImplementedException;
use aminkt\yii2\payment\interfaces\GateInterface;
use aminkt\yii2\payment\interfaces\GateLogInterface;
use aminkt\yii2\payment\models\TransactionSession;
use aminkt\yii2\payment\Payment;
use yii\base\Component;
use yii\helpers\Inflector;

/**
 * Class AbstractGate
 * Base class that other gates should extend from this.
 *
 * @package payment\lib
 */
abstract class AbstractGate extends Component implements GateInterface, GateLogInterface
{
    /** @var int Amount of transaction */
    protected $amount = 0;

    /** @var string $callbackUrl */
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
     * @inheritdoc
     */
    public function dispatchRequest(): bool
    {
        if (Payment::getInstance()->enableByPass() and $_POST['byPassReq']) {
            $this->setOrderId($_POST['orderId'])
                ->setAmount($_POST['amount']);
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public abstract function connect(): GateInterface;

    /**
     * @inheritdoc
     */
    public abstract function payRequest(): GateInterface;

    /**
     * @inheritdoc
     */
    public abstract function redirectToBankFormData(): array;

    /**
     * @inheritdoc
     */
    public abstract function verifyTransaction(): GateInterface;

    /**
     * @inheritdoc
     */
    public function inquiryTransaction(): GateInterface
    {
        throw new NotImplementedException();
    }

    /**
     * @inheritdoc
     */
    public function getCallbackUrl(): string
    {
        return $this->callbackUrl;
    }

    /**
     * @inheritdoc
     */
    public function setCallbackUrl($callbackUrl): GateInterface
    {
        $this->callbackUrl = \Yii::$app->getUrlManager()->createAbsoluteUrl($callbackUrl);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public abstract function getRequest(): array;

    /**
     * @inheritdoc
     */
    public abstract function getResponse(): array;

    /**
     * @inheritdoc
     */
    public abstract function getStatus(): bool;

    /**
     * @inheritdoc
     */
    public function getAmount(bool $rial = false): int
    {
        if ($rial)
            return $this->amount * 10;

        return $this->amount;
    }

    /**
     * @inheritdoc
     */
    public function setAmount(int $amount): GateInterface
    {
        $minAllowedPrice = Payment::getInstance()->minAmount;
        $maxAllowedPrice = Payment::getInstance()->maxAmount;

        $amount = (integer)$amount;
        if (is_int($amount) and $amount >= $minAllowedPrice) {
            if (!$maxAllowedPrice) {
                $this->amount = $amount;
                return $this;
            } elseif ($maxAllowedPrice >= $amount) {
                $this->amount = $amount;
                return $this;
            }
        }

        throw new InvalidAmountException("Amount should be a numeric value and be grater than $minAllowedPrice in IR Toman", $amount);
    }

    /**
     * @inheritdoc
     */
    public function getAuthority(): string
    {
        if (Payment::getInstance()->enableByPass()) {
            return "000TestAuth" . $this->getOrderId();
        }
        return $this->authority;
    }

    /**
     * @inheritdoc
     */
    public function setAuthority(string $authority): GateInterface
    {
        $this->authority = $authority;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getOrderId(): string
    {
        $isBypassEnable = Payment::getInstance()->enableByPass();
        if ($isBypassEnable) {
            return '000' . $this->orderId;
        }
        return $this->orderId;
    }

    /**
     * @inheritdoc
     */
    public function setOrderId($orderId): GateInterface
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTrackingCode(): string
    {
        return $this->trackingCode ?? '000DISPATCHING_REQUEST000';
    }

    /**
     * @inheritdoc
     */
    public function setTrackingCode(string $trackingCode): GateInterface
    {
        $this->trackingCode = $trackingCode;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCardPan(): ?string
    {
        return $this->cardPan;
    }

    /**
     * @inheritdoc
     */
    public function setCardPan(string $cardPan): GateInterface
    {
        $this->cardPan = $cardPan;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCardHash(): ?string
    {
        return $this->cardHash;
    }

    /**
     * @inheritdoc
     */
    public function setCardHash(string $cardHash): GateInterface
    {
        $this->cardHash = $cardHash;
        return $this;
    }

    /**
     * Magic method to handle some method that not implemented.
     *
     * @param string $name
     * @param array  $params
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
     * Return identity data.
     *
     * @param $item
     *
     * @return mixed
     */
    public function getIdentityData($item)
    {
        return $this->identityData[$item];
    }

    /**
     * Set identity data.
     *
     * @param array $identityData
     *
     * @return \aminkt\yii2\payment\gates\AbstractGate
     */
    public function setIdentityData(array $identityData): AbstractGate
    {
        $this->identityData = $identityData;
        return $this;
    }

    /**
     * Return transaction model of current pay request.
     * This may return null in pay request.
     *
     * > Call this method after dispatch method. when order id setted before.
     *
     * @return \aminkt\yii2\payment\models\TransactionSession|null
     */
    public function getTransactionModel(): ?TransactionSession
    {
        $session = TransactionSession::findOne($this->getOrderId());
        if ($session)
            return $session;
        \Yii::warning("Transaction session model not found.");
        return null;
    }

}