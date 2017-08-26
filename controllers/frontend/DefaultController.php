<?php

namespace aminkt\payment\controllers\frontend;

use aminkt\payment\components\Payment;
use yii\web\Controller;

/**
 * Default controller for the `payment` module
 */
class DefaultController extends Controller
{


    public function beforeAction($action)
    {
        if($action->id == 'verify' or $action->id == 'send'){
            $this->enableCsrfValidation = false;
        }
        return parent::beforeAction($action);
    }

    /**
     * Renders the index view for the module.
     * @return string
     */
    public function actionIndex()
    {
        $payment = \aminkt\payment\Payment::getInstance()->payment;
        $payment->payRequest(100, time());
        return $this->render('index');
    }

    /**
     * Redirect site to bank page.
     * @return string
     */
    public function actionSend(){
        $data = \Yii::$app->getSession()->get(Payment::SESSION_NAME_OF_BANK_POST_DATA);
        $data = json_decode($data, true);
        if(array_key_exists('redirect', $data) and isset($data['redirect'])){
            return $this->redirect($data->redirect);
        }else{
            return $this->render('send', [
                'data'=>$data
            ]);
        }

    }

    /**
     * Verify bank transaction.
     */
    public function actionVerify(){
        $payment = \aminkt\payment\Payment::getInstance()->payment;
        $verify = $payment->verify();
        return $this->render('verify', [
            'verify'=>$verify,
        ]);
    }
}
