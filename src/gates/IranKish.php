<?php


namespace aminkt\yii2\payment\gates;

use aminkt\exceptions\ConnectionException;
use aminkt\yii2\payment\interfaces\GateInterface;
use SoapClient;

/**
 * Class IranKish
 *
 * Implement Abstract gate to implement Parsian bank gate.
 *
 * @method string getIdentityMerchantId()  Return MerchantId
 * @method string getIdentityPayRequestUrl()  Web service url that gate object will send pay request to that.
 * @method string getIdentityVerifyUrl()  Verify transaction url.
 * @method string getIdentityGateAddress()  Gate address that user will redirect to that,
 *
 * @package aminkt\yii2\payment\gates
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
class IranKish extends AbstractGate
{
    public $status;
    private $token;
    private $response = [];
    private $request = [];

    /**
     * @inheritdoc
     */
    public function connect(): GateInterface
    {
        $params = array(
            "amount" => $this->getAmount(true),
            "merchantId" => $this->getIdentityMerchantId(),
            "invoiceNo" => $this->getOrderId(),
            "revertURL" => $this->getCallbackUrl(),
        );

        $this->request = $params;

        $client = new SoapClient($this->getIdentityPayRequestUrl(), array('soap_version' => SOAP_1_1));

        try {
            $result = $client->__soapCall("MakeToken", array($params));


            if ($result->MakeTokenResult->token) {
                $this->response = [
                    'token' => $result->MakeTokenResult->token,
                ];
                $this->token = $result->MakeTokenResult->token;
                $this->status = true;
                return $this;
            } else {
                $this->status = false;
                \Yii::warning("Token not generated from irankish.");
            }
        } catch (\Exception $ex) {
            $this->status = false;
            \Yii::error($ex->getMessage(), self::class);
            \Yii::error($ex, self::class);
        }

        throw new ConnectionException("Can not connect to IraniKish gate.");
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
        $data = [
            'action' => $this->getIdentityGateAddress(),
            'method' => 'post',
            'inputs' => [
                'token' => $this->token,
                'merchantId' => $this->getIdentityMerchantId()
            ]
        ];
        $this->request = $data;
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function verifyTransaction(): GateInterface
    {
        $this->dispatchRequest();


        if ($this->getAuthority() and $this->getStatus() and $this->token) {

            $params = array(
                "token" => $this->token,
                "merchantId" => $this->getIdentityMerchantId(),
                "referenceNumber" => $this->getAuthority(),
            );


            $this->request = $params;

            $client = new SoapClient($this->getIdentityVerifyUrl(), array('soap_version' => SOAP_1_1));

            try {
                $result = $client->__soapCall("KicccPaymentsVerification", array($params));


                $this->response['post'] = $_POST;

                if ($result > 0 and $result == $this->getAmount(true)) {
                    $this->status = true;
                    return $this;
                } else {
                    $this->status = false;
                    \Yii::warning("Can not verify user transaction.", static::class);
                }
            } catch (\Exception $ex) {
                $this->status = false;
                \Yii::warning($ex->getMessage(), static::class);
            }
        } else {
            $this->status = false;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dispatchRequest(): bool
    {
        parent::dispatchRequest();

        if (isset($_POST['ResultCode'])) {
            $this->status = $_POST['ResultCode'];
        } else {
            $this->status = false;
        }

        if (isset($_POST['Token'])) {
            $this->token = $_POST['Token'];
        }

        if (isset($_POST['ReferenceId'])) {
            $this->setAuthority($_POST['ReferenceId']);
        }

        if (isset($_POST['InvoiceNumber'])) {
            $this->setOrderId($_POST['InvoiceNumber']);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): bool
    {
        return ($this->status == 100 or $this->status === true);
    }

    /**
     * @inheritdoc
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * @inheritdoc
     */
    public function getResponse(): array
    {
        return $this->response;
    }
}