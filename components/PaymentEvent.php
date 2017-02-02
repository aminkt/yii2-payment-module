<?php
/**
 * Created by Amin Keshavarz
 * Date: 01/02/2017
 * Time: 04:47 PM
 * Created in telbit project
 */

namespace common\modules\payment\components;


use payment\lib\AbstractGate;
use yii\base\Event;

class PaymentEvent extends Event
{
    /** @var  AbstractGate $gate */
    public $gate;

    /** @var  integer $time */
    public $time;

}