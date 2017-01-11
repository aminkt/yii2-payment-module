<?php

namespace payment;

/**
 * payment module definition class
 */
class Payment extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'payment\controllers\frontend';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // initialize the module with the configuration loaded from config.php
        \Yii::configure($this, require(__DIR__ . '/config.php'));
    }
}
