<?php


namespace aminkt\yii2\payment\actions;

use aminkt\yii2\payment\Payment;

/**
 * Class VerfiyAction.
 *
 * This action will handle verify bank transaction.
 *
 * By attaching this action to your controller when user redirected from bank this action will verify payment.
 * Even you use REST API or not this actions will should use as a regular web controller and the controller that want
 * to use this action, should extend from \yii\web\controller. Becuase this route will redirect user to bank and then
 * verify returned.
 *
 * @package aminkt\yii2\payment\actions
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
class VerfiyAction extends \yii\base\Action
{
    public function run()
    {
        $verify = Payment::getInstance()
            ->getPayment()
            ->verify();
    }
}