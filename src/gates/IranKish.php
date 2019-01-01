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
 *
 * @package aminkt\yii2\payment\gates
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
class IranKish extends AbstractGate
{
    public static $payRequestUrl = 'https://ikc.shaparak.ir/XToken/Tokens.xml';
    public static $verifyUrl = "https://ikc.shaparak.ir/XVerify/Verify.xml";
    public static $gateAddress = 'https://ikc.shaparak.ir/TPayment/Payment/index';

    private $token;
    private $terminalNumber;

    public $status;
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

        $client = new SoapClient(static::$payRequestUrl, array('soap_version'   => SOAP_1_1));

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
    public function dispatchRequest(): bool
    {
        parent::dispatchRequest();

        if (isset($_POST['status'])) {
            $this->status = $_POST['status'];
        } else {
            $this->status = $_POST['status'];
        }

        if (isset($_POST['Token'])) {
            $this->token = $_POST['Token'];
        }

        if (isset($_POST['RRN'])) {
            $this->setAuthority($_POST['RRN']);
        }

        if (isset($_POST['OrderId'])) {
            $this->setOrderId($_POST['OrderId']);
        }

        if (isset($_POST['HashCardNumber'])) {
            $this->setCardHash($_POST['HashCardNumber']);
        }

        if(isset($_POST['CardNumberMasked'])) {
            $this->setCardPan($_POST['CardNumberMasked']);
        }

        if (isset($_POST['TerminalNo'])) {
            $this->terminalNumber = $_POST['TerminalNo'];
        }

        return true;
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

        if ($this->getAuthority() > 0 and $this->getStatus() and $this->token) {

            $params = array(
                "LoginAccount" => $this->getIdentityPin(),
                "Token" => $this->token
            );

            $this->request = $params;

            $client = new SoapClient (static::$verifyUrl);

            try {
                $result = $client->ConfirmPayment(array(
                    "requestData" => $params
                ));

                $this->response['verify'] = [
                    'Status' => $result->ConfirmPaymentResult->Status,
                    'Message' => $result->ConfirmPaymentResult->Message
                ];

                $this->response['post'] = $_POST;

                if ($result->ConfirmPaymentResult->Status == '0') {
                    $this->status = $result->ConfirmPaymentResult->Status;
                    return $this;
                } else {
                    $this->status = $result->ConfirmPaymentResult->Status;
                    \Yii::warning($result->ConfirmPaymentResult->Status, static::class);
                    \Yii::warning($result->ConfirmPaymentResult->Message, static::class);
                }
            } catch (\Exception $ex) {
                $this->status = false;
                \Yii::warning($ex->getMessage(), static::class);
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): bool
    {
        return ($this->status == 0 or $this->status === true);
    }

    /**
     * @inheritdoc
     */
    public function getRequest(): array
    {
        return $this->getRequest();
    }

    /**
     * @inheritdoc
     */
    public function getResponse(): array
    {
        return $this->getResponse();
    }
}