<?php

namespace aminkt\payment\lib;

use nusoap_client;
use aminkt\payment\components\Payment;

/**
 * Class SamanGate
 * @package payment\lib
 */
class SamanGate extends AbstractGate
{
    public static $transBankName = 'Saman';
    public static $gateId = '61889632asdW5452';

    private $client;
    private $soapProxy;

    private function connectToWebservice(){
        try{
            $this->client=new nusoap_client($this->config['webService'],'wsdl');
            $this->soapProxy= $this->client->getProxy() ;
            return true;
        }catch(\Exception $e){
            Payment::addError($e->getMessage());
        }
        return false;
    }

    /**
     * Prepare data and config gate for payment.
     * @return mixed
     */
    public function payRequest()
    {
        if(!parent::payRequest())
            return false;
        $bankUrl = $this->getIdentityData('bankGatewayAddress');
        $amount =$this->getPrice();
        $mid = $this->getIdentityData('MID');
        $resNum = $this->getFactorNumber();
        $callBack = $this->getCallbackUrl();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $bankUrl);
        curl_setopt($ch, CURLOPT_POSTFIELDS,"Amount=$amount&MID=$mid&ResNum=$resNum&RedirectURL=$callBack");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * Send user to bank page.
     * @return mixed
     */
    public function sendToBank()
    {
        $bankUrl = $this->getIdentityData('bankGatewayAddress');
        $amount =$this->getPrice();
        $mid = $this->getIdentityData('MID');
        $resNum = $this->getFactorNumber();
        $callBack = $this->getCallbackUrl();
        $data = [
            'bankUrl'=>$bankUrl,
            'post'=>[
                'Amount'=>$amount,
                'MID'=>$mid,
                'ResNum'=>$resNum,
                'RedirectURL'=>$callBack
            ]
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
        $this->status =  $request->post('State');
        $this->setTransTrackingCode($request->post('RefNum'));
        $this->setFactorNumber($request->post('ResNum'));
        $cardNumber =  $request->post('SecurePan');
        $bankResponse = $request->post();

        if(!isset($this->status) or strtolower($this->status) !='ok'){
            Payment::addError('پرداخت نا موفق', 'Invalid status', true);
            return false;
        }
        try{
            if(!$this->connectToWebservice()){
                Payment::addError('امکان اتصال به درگاه وجود ندارد');
                return false;
            }


            $mid  = $this->getIdentityData('MID'); // شماره مشتری بانک سامان

            $result = $this->soapProxy->VerifyTransaction($this->getTransTrackingCode(), $mid);


            $this->status = $result;
            // مغایرت و برگشت دادن وجه به حساب مشتری
            if($result>0)
            {
                return $this;
            }
        }catch(\Exception $e){
            Payment::addError($e->getMessage());
        }


        Payment::incrementBlockCounter();
        return false;
    }
}