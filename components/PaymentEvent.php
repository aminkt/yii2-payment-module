<?php
/**
 * Created by Amin Keshavarz
 * Date: 01/02/2017
 * Time: 04:47 PM
 * Created in telbit project
 */

namespace aminkt\payment\modules\payment\components;


use aminkt\payment\lib\AbstractGate;
use yii\base\Event;

class PaymentEvent extends Event
{
    /** @var  AbstractGate $gate */
    public $gate;

    /** @var  integer $time */
    public $time;

}