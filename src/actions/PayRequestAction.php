<?php


namespace aminkt\yii2\payment\actions;

use aminkt\yii2\payment\Payment;
use yii\web\BadRequestHttpException;

/**
 * Class PayRequestAction.
 *
 * This action will handle pay request action.
 *
 * By attaching this action to your controller when user open this route, will redirect to bank page. after payment
 * should redirect to verify action. Even you use REST API or not this actions will should use as a regular web
 * controller and the controller that want to use this actions should extend from \yii\web\controller.
 * Becuase this route will redirect user to bank and then verify returned.
 *
 *
 * @see     \aminkt\yii2\payment\actions\VerfiyAction
 * @see     \yii\web\Controller
 *
 * @package aminkt\yii2\payment\actions
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
class PayRequestAction extends \yii\base\Action
{
    /**
     * Contain order model related to this payment request.
     *
     * @var \aminkt\yii2\payment\interfaces\OrderInterface  $orderModel
     *
     * @author  Amin Keshavarz <ak_1596@yahoo.com>
     */
    public $orderModel;

    /**
     * Description of payment.
     *
     * @var string  $description
     *
     * @author  Amin Keshavarz <ak_1596@yahoo.com>
     */
    public $description;

    /**
     * Run pay request action.
     *
     * @return void
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     *
     * @author  Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function run()
    {
        if(!$this->orderModel){
            throw new BadRequestHttpException("Model not defined.");
        }

        $payment = Payment::getInstance()
            ->getPayment()
            ->payRequest(
                $this->orderModel->getPayAmount(),
                $this->orderModel->getId(),
                $this->description
            );
    }
}