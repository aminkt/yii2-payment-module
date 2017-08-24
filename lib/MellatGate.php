<?php

namespace aminkt\payment\lib;

use aminkt\payment\components\Payment;
use aminkt\payment\exceptions\ConnectionException;
use aminkt\payment\exceptions\VerifyPaymentException;
use SoapClient;
use SoapFault;

/**
 * Class MellatGate
 *
 * @method string getIdentityTerminalId() Return terminal id.
 * @method string getIdentityUserName() Return user name.
 * @method string getIdentityPassword() Return Password.
 * @method string getIdentityPayerId()  Return payer id.
 * @method string getIdentityWebService()   Return webservice address.
 * @method string getIdentityBankGatewayAddress()   Return bank gateway address.
 *
 * @package payment\lib
 */
class MellatGate extends AbstractGate
{
    public static $transBankName = 'Mellat';
    public static $gateId = 'Masdawf8';

    private $client;
    private $namespace;

    protected $response;
    protected $statusCode;
    protected $request;

    /**
     * @inheritdoc
     */
    public function dispatchRequest()
    {
        $this->statusCode = $_POST['ResCode'] == '0';
        if (isset($_POST['ResCode']) && $_POST['ResCode'] == '0' && !empty($_POST['RefId'])) {
            $this->setAuthority($_POST['RefId']);
            if (!empty($_POST['CardHolderPan'])) {
                //todo when user pay with mobile mellat will post CardHolderPan with 2 less stars
                if (strlen($_POST['CardHolderPan']) == 14) {
                    $_POST['CardHolderPan'] = str_replace('****', '******', $_POST['CardHolderPan']);
                }
                $this->setCardPan($_POST['CardHolderPan']);
                $this->setCardHash($_POST['CardHolderInfo']);
                $this->setTrackingCode($_POST['SaleReferenceId']);
            }
            return true;
        }
        return false;
    }

    private function connectToWebService(){
        try{
            $this->client = new SoapClient($this->identityData['webService'], array(
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                ));
            $this->namespace = 'http://interfaces.core.sw.bps.com/';
            return true;
        }catch (SoapFault $fault){
            throw $fault;
        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * @inheritdoc
     */
    public function payRequest()
    {
        try{
            if(!$this->connectToWebService()){
                throw new ConnectionException("Can not connect to Mellat bank web service.", 1);
            }

            // قرار دادن پارامترها در یک آرایه
            $parameters = array(
                'terminalId' => $this->getIdentityTerminalId(),
                'userName' => $this->getIdentityUserName(),
                'userPassword' => $this->getIdentityPassword(),
                'orderId' => $this->getOrderId(),
                'amount' => $this->getAmount(),
                'localDate' => $this->localDate(),
                'localTime' => $this->localTime(),
                'additionalData' => null,
                'callBackUrl' => $this->getCallbackUrl(),
                'payerId' => $this->identityData['payerId'],
            );
            $this->request = $parameters;

            // ارسال درخواست پرداخت به سرور بانک
            $result1 = $this->client->bpPayRequest($parameters, $this->namespace);
            if (is_soap_fault($result1)) {
                throw new ConnectionException("Mellat bank cant handle pay request action.", 2);
            }

            $resultStr = $result1->return;
            $res = explode (',',$resultStr);
            $this->response = $res;

            if(is_array($res)){
                $resCode = $res[0];
                if($resCode == 0){
                    $this->setAuthority($res[1]);
                    return true;
                }else{
                    throw new ConnectionException("Mellat bank is not in service.", 3);
                }
            }
        }catch (SoapFault $f) {
            throw new ConnectionException($f->getMessage(), 4, $f);
        }catch (\Exception $e){
            throw new ConnectionException($e->getMessage(), 5, $e);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function redirectToBankFormData()
    {
        $bankUrl = $this->getIdentityData('bankGatewayAddress');
        $refId = $this->getAuthority();
        $data = [
            'action' => $bankUrl,
            'method' => 'post',
            'inputs' => [
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
        $status = $this->dispatchRequest();

        if (!$status) {
            throw new VerifyPaymentException("Payment become failed because of " . $this->statusCode, $this->statusCode);
        }

        try{
            if(!$this->connectToWebService()){
                throw new ConnectionException("Can not connect to Mellat bank web service.", 1);
            }

            // قرار دادن پارامترها در یک آرای
            $parameters = array(
                'terminalId' => $this->getIdentityTerminalId(),
                'userName' => $this->getIdentityUserName(),
                'userPassword' => $this->getIdentityPassword(),
                'orderId' => $this->getOrderId(),
                'saleOrderId' => $this->getOrderId(),
                'saleReferenceId' => $this->getTrackingCode(),
            );

            $this->request = $parameters;

            $result = $this->client->bpVerifyRequest($parameters, $this->namespace);
            $resultStr = $result->return;
            $res = explode(',', $resultStr);

            if(is_array($res)){
                $resCode = $res[0];
                $this->response = array_merge($_POST, $res);
                if($resCode == 0 and $this->settleTransaction()) {
                    return $this;
                }
            }else
                throw new \RuntimeException("Response not converted to array.", 2);

            return false;
        }catch(\Exception $e){
            throw new VerifyPaymentException($e->getMessage(), $e->getCode(), $e);
        }

        return false;
    }

    /**
     * Send settlement request to bank.
     * @return bool
     */
    private function settleTransaction()
    {
        $parameters = [
            'terminalId' => $this->getIdentityTerminalId(),
            'userName' => $this->getIdentityUserName(),
            'userPassword' => $this->getIdentityPassword(),
            'orderId' => $this->getOrderId(),
            'saleOrderId' => $this->getOrderId(),
            'saleReferenceId' => $this->getTrackingCode(),
        ];

        $result = $this->client->bpSettleRequest($parameters, $this->namespace);
        $resultStr = $result->return;
        $res = explode(',', $resultStr);

        $resCode = $res[0];
        $this->statusCode = $resCode;
        if($resCode == 0){
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function inquiryTransaction()
    {
        parent::inquiryTransaction();
        try{
            if(!$this->connectToWebService()){
                throw new ConnectionException("Can not connect to Mellat bank web service.", 1);
            }

            // قرار دادن پارامترها در یک آرای
            $parameters = array(
                'terminalId' => $this->getIdentityTerminalId(),
                'userName' => $this->getIdentityUserName(),
                'userPassword' => $this->getIdentityPassword(),
                'orderId' => $this->getOrderId(),
                'saleOrderId' => $this->getOrderId(),
                'saleReferenceId' => $this->getTrackingCode(),
            );
            $this->request = $parameters;

            $result = $this->client->bpInquiryRequest($parameters, $this->namespace);
            $resultStr = $result->return;
            $res = explode(',', $resultStr);
            $this->response = $res;
            if(is_array($res)){
                $resCode = $res[0];
                $this->statusCode = $resCode;
                if($resCode == 0){
                    return true;
                }
            }

        } catch (\Exception $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
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

    /**
     * Return bank requests as array.
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return bank response as array.
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

}