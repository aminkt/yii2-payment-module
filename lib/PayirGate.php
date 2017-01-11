<?php
namespace payment\lib;
use payment\components\Payment;

/**
 * Class PayirGate
 * @package payment\lib
 */
class PayirGate extends AbstractGate
{
    public static $transBankName = 'Payir';
    public static $gateId = 'Py1';

    /**
     * Prepare data and config gate for payment.
     * @return mixed
     */
    public function payRequest()
    {
        parent::payRequest();
        $api = $this->getIdentityData('api');
        $amount = $this->getPrice();
        $redirect = $this->getCallbackUrl();
        $factorNumber = $this->getFactorNumber();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getIdentityData('bankPayReqAddress'));
        curl_setopt($ch, CURLOPT_POSTFIELDS,"api=$api&amount=$amount&redirect=$redirect&factorNumber=$factorNumber");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($res);

        $this->status = $result->status;
        if($result->status) {
            $this->setTransactionId($result->transId);
            return true;
        } else {
            echo $result->errorMessage;
        }
        return false;
    }

    /**
     * Send user to bank page.
     * @return mixed
     */
    public function sendToBank()
    {
        $transId = $this->getTransactionId();
        $bankUrl = $this->getIdentityData('bankGatewayAddress').'/'.$transId;
        $data = [
            'redirect'=>$bankUrl,
        ];
        return $data;
    }

    /**
     * Verify Transaction if its paid. this method should call in callback from bank.
     * @return AbstractGate|boolean
     */
    public function verifyTransaction()
    {
        parent::verifyTransaction();
        $request = \Yii::$app->request;
        $api = $this->getIdentityData('api');
        $bankResponse = $request->post();
        $this->setTransTrackingCode($request->post('transId'));
        $this->setFactorNumber($request->post('factorNumber'));
        $this->setTransactionId($request->post('transId'));
        $transId = $this->getTransactionId();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getIdentityData('bankVerifyAddress'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&transId=$transId");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($res);

        $this->status = $result->status;

        if($this->status == 1){
            $this->setPrice($result->amount/10);
            return true;
        }else{
            Payment::addError('پرداخت نا موفق', $this->status, true);
            Payment::addError(\Yii::$app->request->post('message'), $this->status, true);
        }

        Payment::incrementBlockCounter();
        return false;
    }
}