<?php


namespace aminkt\yii2\payment\gates;

use aminkt\exceptions\ConnectionException;
use aminkt\yii2\payment\interfaces\GateInterface;
use SoapClient;

/**
 * Class Parsian
 *
 * Implement Abstract gate to implement Parsian bank gate.
 *
 * @method string getIdentityPin()  Return terminal pin code.
 * @method string getIdentityTerminal() Return terminal number.
 * @method string getIdentityPayRequestUrl()  Web service url that gate object will send pay request to that.
 * @method string getIdentityVerifyUrl()  Verify transaction url.
 * @method string getIdentityGateAddress()  Gate address that user will redirect to that,
 *
 * @package aminkt\yii2\payment\gates
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
class Parsian extends AbstractGate
{
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
            "LoginAccount" => $this->getIdentityPin(),
            "Amount" => $this->getAmount(true),
            "OrderId" => $this->getOrderId(),
            "CallBackUrl" => $this->getCallbackUrl()
        );

        $this->request = $params;

        $client = new SoapClient ($this->getIdentityPayRequestUrl());

        try {
            $result = $client->SalePaymentRequest(array(
                "requestData" => $params
            ));

            $this->response = [
                'token' => $result->SalePaymentRequestResult->Token,
                'status' => $result->SalePaymentRequestResult->Status,
            ];

            if ($result->SalePaymentRequestResult->Token && $result->SalePaymentRequestResult->Status === 0) {
                $this->token = $result->SalePaymentRequestResult->Token;
                $this->status = $result->SalePaymentRequestResult->Status;
                return $this;
            } elseif ($result->SalePaymentRequestResult->Status != '0') {
                $this->status = $result->SalePaymentRequestResult->Status;
                $this->response['message'] = $result->SalePaymentRequestResult->Message;
                \Yii::warning($result->SalePaymentRequestResult->Status, self::class);
                \Yii::warning($result->SalePaymentRequestResult->Message, self::class);
            }
        } catch (\Exception $ex) {
            $this->status = false;
            \Yii::error($ex->getMessage(), self::class);
            \Yii::error($ex, self::class);
        }

        throw new ConnectionException("Can not connect to Parsian gate.");
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
        $gateAddress = $this->getIdentityGateAddress();
        return [
            'redirect' => "{$gateAddress}?Token=" . $this->token
        ];
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

            $client = new SoapClient ($this->getIdentityVerifyUrl());

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