<?php

namespace aminkt\yii2\payment\lib;

use aminkt\yii2\payment\exceptions\ConnectionException;
use aminkt\yii2\payment\exceptions\VerifyPaymentException;
use SoapClient;
use SoapFault;
use yii\httpclient\Client;

/**
 * Class Sep gate of Saman bank
 *
 * @method string getIdentityMID() Return terminal id.
 * @method string getIdentityPassword() Return Password.
 * @method string getIdentityWebService()   Return webservice address.
 * @method string getIdentityBankGatewayAddress()   Return bank gateway address.
 *
 * @package payment\lib
 */
class Sep extends AbstractGate
{
    public static $transBankName = 'Saman';
    public static $gateId = 'Saman5W8';
    protected $response;
    protected $stateCode;
    protected $state;
    protected $request;
    private $soapProxy;

    /**
     * @inheritdoc
     */
    public function payRequest()
    {
        $data = [
            'Amount' => $this->getAmount(),
            'ResNum' => $this->getOrderId(),
            'MID' => $this->getIdentityData('MID'),
            'RedirectURL' => $this->getCallbackUrl()
        ];
        $client = new Client();
        $request = $client->createRequest()
            ->setMethod('post')
            ->setUrl($this->getIdentityBankGatewayAddress())
            ->setData($data);
        /** @var \yii\httpclient\Response $response */
        $response = $request->send();
        $this->response = $response->getStatusCode();
        \Yii::warning($this->response);
        \Yii::warning($this->getIdentityBankGatewayAddress());
        \Yii::warning($data);
        if ($this->response == '200') {
            return true;
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function redirectToBankFormData()
    {
        $bankUrl = $this->getIdentityData('bankGatewayAddress');
        $data = [
            'action' => $bankUrl,
            'method' => 'post',
            'inputs' => [
                'Amount' => $this->getAmount(),
                'ResNum' => $this->getOrderId(),
                'MID' => $this->getIdentityData('MID'),
                'RedirectURL' => $this->getCallbackUrl()
            ]
        ];
        $this->request = $data;
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
            throw new VerifyPaymentException("Payment become failed because of " . $this->state, $this->stateCode);
        }

        try {
            if (!$this->connectToWebService()) {
                throw new ConnectionException("Can not connect to Saman bank web service.", 1);
            }

            $this->request = array_merge($_POST, [
                'refNum' => $this->getAuthority(),
                'MID' => $this->getIdentityData('MID')
            ]);
            $this->response = $this->soapProxy->verifyTransaction($this->getAuthority(), $this->getIdentityData('MID'));
            \Yii::info("Amount of transaction: " . $this->getAmount());
            if ($this->response > 0 and $this->response == $this->getAmount()) {
                return $this;
            } else {
                throw new VerifyPaymentException("Verify transaction become failed because of $this->response code", $this->response);
            }

            return false;
        } catch (\Exception $e) {
            throw new VerifyPaymentException($e->getMessage(), $e->getCode(), $e);
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function dispatchRequest()
    {
        $this->stateCode = $_POST['StateCode'];
        $this->state = $_POST['State'];
        $this->setOrderId($_POST['ResNum']);

        if ($this->getStatus() and $this->getOrderId()) {
            $this->setAuthority($_POST['RefNum'])
                ->setTrackingCode($_POST['TRACENO'])
                ->setCardPan($_POST['SecurePan'])
                ->setCardHash($_POST['CID']);
            return true;
        }
        return false;
    }

    /**
     * Return status of pay request, verify or inquiry request.
     * @return boolean
     */
    public function getStatus()
    {
        return $this->state == 'OK';
    }

    private function connectToWebService()
    {
        try {
            \Yii::error($this->getIdentityData('webService'));
            $this->soapProxy = new SoapClient($this->getIdentityWebService(), [
                'encoding' => 'UTF-8',
                'connection_timeout' => 20,
                'cache_wsdl' => WSDL_CACHE_NONE
            ]);
            return true;
        } catch (SoapFault $fault) {
            throw $fault;
        } catch (\ErrorException $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            throw $e;
        }
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