<?php

namespace aminkt\payment\components;


use aminkt\payment\lib\AbstractGate;
use yii\base\Event;

/**
 * Class PaymentEvent
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 * @package aminkt\payment\components
 */
class PaymentEvent extends Event
{
    /** @var  boolean $status Payment status */
    public $status;

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

    /** @var  AbstractGate $gate */
    public $gate;

    /** @var  integer $time */
    public $time;

    /** @var  \aminkt\payment\models\TransactionSession $transactionSession */
    public $transactionSession;

    public function init()
    {
        parent::init();
        $this->time = time();
    }

    /**
     * @return AbstractGate
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * @param AbstractGate $gate
     * @return $this
     */
    public function setGate($gate)
    {
        $this->gate = $gate;
        return $this;
    }

    /**
     * @return \aminkt\payment\models\TransactionSession
     */
    public function getTransactionSession()
    {
        return $this->transactionSession;
    }

    /**
     * @param \aminkt\payment\models\TransactionSession $transactionSession
     * @return $this
     */
    public function setTransactionSession($transactionSession)
    {
        $this->transactionSession = $transactionSession;
        return $this;
    }
}