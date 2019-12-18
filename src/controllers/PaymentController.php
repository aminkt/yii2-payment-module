<?php

namespace aminkt\yii2\payment\controllers;

/**
 * Class PaymentController
 * Handle payment request actions.
 *
 * @author Amin Keshavarz <ak_1596@yahoo.com>
 */
class PaymentController extends \yii\web\Controller
{
    public $enableCsrfValidation = false;

    /**
     * Run pay request action.
     *
     * @return void
     *
     * @author  Amin Keshavarz <ak_1596@yahoo.com>
     * @throws \Exception
     * @throws \yii\web\BadRequestHttpException
     *
     * @author  Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function actionGate($orderId)
    {
        $model = $this->findModel($orderId);

        $payment = \aminkt\yii2\payment\Payment::getInstance()
            ->getPayment()
            ->payRequest(
                $model
            );
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
        $verify = \aminkt\yii2\payment\Payment::getInstance()
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
        $class = \aminkt\yii2\payment\Payment::getInstance()->orderClass;
        $model = $class::findOne($id);
        if (!$model) {
            throw new NotFoundHttpException("Order dose not exist.");
        }
        return $model;
    }
}