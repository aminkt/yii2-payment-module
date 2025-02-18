<?php

namespace aminkt\yii2\payment\components;


use aminkt\yii2\payment\models\TransactionInquiry;
use yii\base\Event;

/**
 * Class PaymentEvent
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 * @package aminkt\payment\components
 */
class PaymentEvent extends Event
{
    /** @var  boolean $status PaymentProvider status */
    public $status;

    /** @var null|TransactionInquiry $transactionInquiry */
    public $transactionInquiry = null;

    /**
     * @return TransactionInquiry|null
     */
    public function getTransactionInquiry()
    {
        return $this->transactionInquiry;
    }

    /**
     * @param TransactionInquiry|null $transactionInquiry
     *
     * @return $this
     */
    public function setTransactionInquiry($transactionInquiry)
    {
        $this->transactionInquiry = $transactionInquiry;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /** @var  \aminkt\yii2\payment\gates\AbstractGate $gate */
    public $gate;

    /** @var  integer $time */
    public $time;

    /** @var  \aminkt\yii2\payment\models\TransactionSession $transactionSession */
    public $transactionSession;

    public function init()
    {
        parent::init();
        $this->time = time();
    }

    /**
     * @return \aminkt\yii2\payment\gates\AbstractGate
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * @param \aminkt\yii2\payment\gates\AbstractGate $gate
     * @return $this
     */
    public function setGate($gate)
    {
        $this->gate = $gate;
        return $this;
    }

    /**
     * @return \aminkt\yii2\payment\models\TransactionSession
     */
    public function getTransactionSession()
    {
        return $this->transactionSession;
    }

    /**
     * @param \aminkt\yii2\payment\models\TransactionSession $transactionSession
     * @return $this
     */
    public function setTransactionSession($transactionSession)
    {
        $this->transactionSession = $transactionSession;
        return $this;
    }
}