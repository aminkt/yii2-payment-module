<?php

namespace aminkt\yii2\payment;
use yii\base\InvalidConfigException;

/**
 * Payment module definition class
 *
 * @property \aminkt\payment\components\Payment $payment    Payment component.
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 * @package aminkt\payment
 */
class Payment extends \yii\base\Module
{
    const BEFORE_PAYMENT_REQUEST = 'before_payment_request';
    const AFTER_PAYMENT_REQUEST = 'after_payment_request';
    const BEFORE_PAYMENT_VERIFY = 'before_payment_verify';
    const AFTER_PAYMENT_VERIFY = 'after_payment_verify';
    const BEFORE_PAYMENT_INQUIRY = 'before_payment_inquiry';
    const AFTER_PAYMENT_INQUIRY = 'after_payment_inquiry';

    /**
     * The order class name. Every payment session should have an order.
     *
     * @var \aminkt\yii2\payment\interfaces\OrderInterface  $orderClass
     */
    public $orderClass;

    /**
     * Min amount define how much price available to pay in bank gates.
     * Default value is 0.
     *
     * @var integer $minAmount
     */
    public $minAmount = 0;

    /**
     * Max amount define how much price available to pay in bank gates.
     * Default value is 10,000,000.
     *
     * @var integer $maxAmount
     */
    public $maxAmount = 10000000;

    /**
     * Configurations of payment component provided by this module.
     *
     * @var array $paymentComponentConfiguration
     */
    public $paymentComponentConfiguration;

    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'aminkt\payment\controllers\frontend';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if(!$this->orderClass) {
            throw new InvalidConfigException("Order calss should define.");
        }

        // initialize the module with the configuration loaded from config.php
        $config = require(__DIR__ . '/config.php');
        if ($this->paymentComponentConfiguration) {
            $config['components']['payment'] = $this->paymentComponentConfiguration;
        }

        \Yii::configure($this, $config);
    }

    /**
     * @inheritdoc
     *
     * @author Amin Keshavarz <amin@keshavarz.pro>
     */
    public static function getInstance()
    {
        if (parent::getInstance())
            return parent::getInstance();

        return \Yii::$app->getModule('payment');
    }

    /**
     * Return payment components.
     *
     * @return \aminkt\yii2\payment\components\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
