<?php

namespace aminkt\yii2\payment\controllers;

use aminkt\yii2\payment\interfaces\OrderInterface;
use aminkt\yii2\payment\Payment;
use Yii;
use yii\base\ExitException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * Class PaymentController
 * Handle payment request actions.
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class PaymentController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * Run pay request action.
     *
     * @param $orderId
     *
     * @return void
     *
     * @throws NotFoundHttpException
     * @throws ExitException
     * @author  Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function actionGate($orderId)
    {
        $model = $this->findModel($orderId);
        $payment = Payment::getInstance()->getPayment()->payRequest($model);
        Yii::$app->end();
    }

    /**
     * Verify bank request if valid.
     *
     * @return string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function actionVerify()
    {
        $verify = Payment::getInstance()
            ->getPayment()
            ->verify();

        return $this->render('verify', [
            'verify'=>$verify,
        ]);
    }

    /**
     * Find order model object.
     *
     * @param $id
     *
     * @return \aminkt\yii2\payment\interfaces\OrderInterface
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function findModel($id)
    {
        /** @var OrderInterface $class */
        $class = Payment::getInstance()->orderClass;
        $model = $class::getById($id);
        if (!$model) {
            throw new NotFoundHttpException("Order dose not exist.");
        }
        return $model;
    }
}