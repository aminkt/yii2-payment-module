<?php
namespace payment\lib;
use payment\components\Payment;
use SoapFault;

/**
 * Class MellatGate
 * @package payment\lib
 */
class MellatGate extends AbstractGate
{
    public static $transBankName = 'Mellat';
    public static $gateId = 'Masdawf8';

    /** @var  string $refId bank referenced if*/
    public $refId;

    private $client;
    private $namespace;

    private function connectToWebService(){
        try{
            $this->client = new \SoapClient($this->identityData['webService'], array(
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                ));
            $this->namespace = 'http://interfaces.core.sw.bps.com/';
            return true;
        }catch (SoapFault $fault){
            Payment::addError('اتصاب با وب سرویس برقرار نشد.');
            Payment::addError($fault->getMessage());
        }catch (\Exception $e){
            Payment::addError('اتصاب با وب سرویس برقرار نشد.');
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
        try{
            if(!$this->connectToWebService()){
                Payment::addError('امکان اتصال به درگاه وجود ندارد');
                return false;
            }
            // قرار دادن پارامترها در یک آرایه
            $parameters = array(
                'terminalId' => $this->getIdentityData('terminalId'),
                'userName' => $this->getIdentityData('userName'),
                'userPassword' => $this->getIdentityData('password'),
                'orderId' => $this->getFactorNumber(),
                'amount' => $this->getPrice(),
                'localDate' => $this->localDate(),
                'localTime' => $this->localTime(),
                'additionalData' => null,
                'callBackUrl' => $this->getCallbackUrl(),
                'payerId' => $this->identityData['payerId'],
            );

            // ارسال درخواست پرداخت به سرور بانک
            $result1 = $this->client->bpPayRequest($parameters, $this->namespace);
            if (is_soap_fault($result1)) {
                new \RuntimeException("Can not connect to Mellat bank web service.");
            }
            $resultStr = $result1->return;
            $res = explode (',',$resultStr);
            if(is_array($res)){
                $resCode = $res[0];
                if($resCode == 0){
                    $this->setTransactionId($res[1]);
                    $result = "0,".$this->getTransactionId();
                    $this->status = $resCode;
                    return true;
                }else{
                    $result = $resCode.",null";
                    $this->status = $resCode;
                    Payment::addError('بانک ملت درحال حاضر خارج از سرویس میباشد.', $resCode);
                }
            }
        }catch (SoapFault $f) {
            Payment::addError('خطا در ارسال درخواست به بانک ملت.');
            Payment::addError($f->getMessage());
        }catch (\Exception $e){
            Payment::addError('خطا در ارسال درخواست به بانک ملت.');
            Payment::addError($e->getMessage());
        }

        return false;
    }

    /**
     * Send user to bank page.
     * @return mixed
     */
    public function sendToBank()
    {
        $bankUrl = $this->getIdentityData('bankGatewayAddress');
        $refId = $this->getTransactionId();
        $data = [
            'bankUrl'=>$bankUrl,
            'post'=>[
                'RefId'=>$refId,
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
        $bankResponse = $request->post();
        $resCode = $request->post('ResCode');

        $this->status = $resCode;

        $this->setTransactionId($request->post('RefId'))
            ->setFactorNumber($request->post('SaleOrderId'))
            ->setTransTrackingCode($request->post('SaleReferenceId'));
        $cardNumber = $request->post('CardHolderPan');
        $additionalData = $request->post('additionalData');

        if($this->status !='0'){
            Payment::addError('پرداخت نا موفق', $this->status, true);
            return false;
        }

        try{
            if(!$this->connectToWebService()){
                Payment::addError('امکان اتصال به درگاه وجود ندارد');
                return false;
            }

            // قرار دادن پارامترها در یک آرای
            $parameters = array(
                'terminalId' => $this->getIdentityData('terminalId'),
                'userName' => $this->getIdentityData('userName'),
                'userPassword' => $this->getIdentityData('password'),
                'orderId' => $this->getFactorNumber(),
                'saleOrderId' => $this->getFactorNumber(),
                'saleReferenceId' => $this->getTransTrackingCode(),
            );

            $result = $this->client->bpVerifyRequest($parameters, $this->namespace);
            $resultStr = $result->return;
            $res = explode(',', $resultStr);

            if(is_array($res)){
                $resCode = $res[0];
                $this->status = $resCode;

                if($resCode == 0 and $this->settleTransaction()) {
                    return $this;
                }
            }else
                Payment::addError('خطای سیستمی', -1);

            Payment::incrementBlockCounter();
            return false;
        }catch(\Exception $e){
            Payment::addError($e->getMessage());
        }

        return false;
    }

    public function settleTransaction()
    {
        $parameters = [
            'terminalId' => $this->getIdentityData('terminalId'),
            'userName' => $this->getIdentityData('userName'),
            'userPassword' => $this->getIdentityData('password'),
            'orderId' => $this->getFactorNumber(),
            'saleOrderId' => $this->getFactorNumber(),
            'saleReferenceId' => $this->getTransTrackingCode(),
        ];

        $result = $this->client->bpSettleRequest($parameters, $this->namespace);
        $resultStr = $result->return;
        $res = explode(',', $resultStr);

        $resCode = $res[0];
        $this->status = $resCode;
        if($resCode == 0){
            return true;
        }

        return false;
    }

    public function inquiryTransaction()
    {
        parent::inquiryTransaction();
        try{
            if(!$this->connectToWebService()){
                Payment::addError('امکان اتصال به درگاه وجود ندارد');
                return false;
            }

            // قرار دادن پارامترها در یک آرای
            $parameters = array(
                'terminalId' => $this->getIdentityData('terminalId'),
                'userName' => $this->getIdentityData('userName'),
                'userPassword' => $this->getIdentityData('password'),
                'orderId' => $this->getFactorNumber(),
                'saleOrderId' => $this->getFactorNumber(),
                'saleReferenceId' => $this->getTransTrackingCode(),
            );

            $result = $this->client->bpInquiryRequest($parameters, $this->namespace);
            $resultStr = $result->return;
            $res = explode(',', $resultStr);

            if(is_array($res)){
                $resCode = $res[0];
                $this->status = $resCode;
                if($resCode == 0){
                    return true;
                }
            }

        } catch (\Exception $e) {
            Payment::addError($e->getMessage());
        }
        return false;
    }

    /**
     * Return date
     * @return string
     */
    private function localDate(){
        return date("Ymd");
    }

    /**
     * Return time
     * @return string
     */
    private function localTime(){
        return date("Gis");
    }
}