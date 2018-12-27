<?php

namespace aminkt\yii2\payment\gates;

use aminkt\exceptions\ConnectionException;
use aminkt\exceptions\VerifyPaymentException;
use aminkt\yii2\payment\interfaces\GateInterface;
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
    protected $response;
    protected $stateCode;
    protected $state;
    protected $request;
    protected $status=false;
    private $soapProxy;


    /**
     * @inheritdoc
     */
    public function connect(): GateInterface
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
            $this->status = true;
            return $this;
        }
        throw new ConnectionException("Can not connect to saman gate.");
    }

    /**
     * @inheritdoc
     */
    public function payRequest(): GateInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function redirectToBankFormData(): array
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
    public function verifyTransaction(): GateInterface
    {
        $this->status = false;
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
                $this->status = true;
                return $this;
            } else {
                throw new VerifyPaymentException("Verify transaction become failed because of $this->response code", $this->response);
            }

            return $this;
        } catch (\Exception $e) {
            throw new VerifyPaymentException($e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dispatchRequest(): bool
    {
        parent::dispatchRequest();

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
    public function getStatus():bool
    {
        return ($this->state == 'OK' and $this->status === true);
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
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Return bank response as array.
     * @return array
     */
    public function getResponse(): array
    {
        return $this->response;
    }
}