<?php

namespace aminkt\payment;

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
    const EVENT_PAYMENT_REQUEST = 'payment_req';
    const EVENT_PAYMENT_VERIFY = 'payment_verify';
    const EVENT_PAYMENT_INQUIRY = 'payment_inquiry';

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
        // initialize the module with the configuration loaded from config.php
        $config = require(__DIR__ . '/config.php');
        if ($this->paymentComponentConfiguration) {
            $config['components']['payment'] = $this->paymentComponentConfiguration;
        }
        \Yii::configure($this, $config);
    }

    public static function getInstance()
    {
        if (parent::getInstance())
            return parent::getInstance();

        return \Yii::$app->getModule('payment');
    }

    /**
     * Return payment components.
     * @return components\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }
}
