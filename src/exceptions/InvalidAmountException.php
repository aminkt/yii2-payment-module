<?php

namespace aminkt\yii2\payment\exceptions;

use Throwable;

/**
 * Class InvalidAmountException
 *
 * Throw if amount is not valid.
 *
 * @package common\exceptions
 */
class InvalidAmountException extends \InvalidArgumentException
{
    public $amount;
    public $wage;

    public function __construct($message = "", $amount, $wage = 0, $code = 0, Throwable $previous = null)
    {
        $this->amount = $amount;
        $this->wage = $wage;
        parent::__construct($message, $code, $previous);
    }
}