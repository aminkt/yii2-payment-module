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
    /** @var  AbstractGate $gate */
    public $gate;

    /** @var  integer $time */
    public $time;

}