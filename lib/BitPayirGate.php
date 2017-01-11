<?php
namespace payment\lib;
use payment\components\Payment;

/**
 * Class BitPayirGate
 * @package payment\lib
 */
class BitPayirGate extends AbstractGate
{
    public static $transBankName = 'BitPayir';
    public static $gateId = 'bPy2';

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
        $name = '';
        $email = '';
        $description = '';
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$this->getIdentityData('bankPayReqAddress'));
        curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&amount=$amount&redirect=$redirect&factorId=$factorNumber&name=$name&email=$email&description=$description");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->status = $res;

        if($res > 0 && is_numeric($res))
        {
            return true;
        }else{
            Payment::addError("درگاه بیت پی در حال حاضر قادر به سرویس دهی نیست.", $this->status);
        }

        return false;
    }

    /**
     * Send user to bank page.
     * @return mixed
     */
    public function sendToBank()
    {
        $result = $this->status;
        $bankUrl = $this->getIdentityData('bankGatewayAddress').$result;
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
        $this->setTransTrackingCode($request->post('trans_id'));
        $this->setTransactionId($request->post('id_get'));
        $transId = $this->getTransactionId();
        $trackingCode = $this->getTransTrackingCode();

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$this->getIdentityData('bankVerifyAddress'));
        curl_setopt($ch,CURLOPT_POSTFIELDS,"api=$api&id_get=$transId&trans_id=$trackingCode");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        $res = curl_exec($ch);
        curl_close($ch);

        $this->status = $res;

        if($this->status == 1){
            return true;
        }else{
            Payment::addError('پرداخت نا موفق', $this->status, true);
        }

        Payment::incrementBlockCounter();
        return false;
    }
}