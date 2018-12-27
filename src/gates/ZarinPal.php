<?php

namespace aminkt\yii2\payment\gates;

use aminkt\yii2\payment\components\Payment;
use aminkt\yii2\payment\interfaces\GateInterface;
use yii\httpclient\Client;

/**
 * Class ZarinPal gate of Zarinpal
 *
 * @method string getIdentityMerchantCode() Return merchant code.
 * @method string getIdentityIsZarinGate() Return true if want to use zarin gate.
 * @method string getIdentityEnableSandbox() Return true if want to use sandbox gate.
 * @method string getIdentityRedirectUrl() Return redirect address.
 * @method string getIdentityPayRequestUrl() Return pay request address.
 * @method string getIdentityVerifyRequestUrl() Return verify request address.
 *
 * @package payment\lib
 */
class ZarinPal extends AbstractGate
{
    public $status = false;
    public $statusCode;
    private $request;
    private $response;

    /**
     * Dispatch payment response from bank.
     *
     * @return boolean
     */
    public function dispatchRequest(): bool
    {
        parent::dispatchRequest();

        $this->setOrderId($_GET['oi']);
        if (isset($_GET['Authority'])) {
            $this->setAuthority($_GET['Authority']);
            return true;
        }
        return false;
    }



    /**
     * @inheritdoc
     */
    public function connect(): GateInterface
    {
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function payRequest(): GateInterface
    {
        $this->status = false;
        $data = [
            'MerchantID' => $this->getIdentityMerchantCode(),
            'Amount' => $this->getAmount(false),
            'CallbackURL' => $this->getCallbackUrl(),
            'Description' => "Buy"
        ];

        $this->request = $data;

        $client = new Client();
        $request = $client->createRequest()
            ->setMethod('post')
            ->setUrl($this->getIdentityPayRequestUrl())
            ->setContent(json_encode($data))
            ->setHeaders([
                'Content-Type' => 'application/json'
            ])
            ->setData($data);
        /** @var \yii\httpclient\Response $response */
        $response = $request->send();
        $response = json_decode($response->getContent(), true);
        $this->response = $response;

        $this->statusCode = $response['Status'];

        if (isset($response['Authority'])) {
            $this->status = true;
            $this->setAuthority($response['Authority']);
        }

        return $this;
    }

    /**
     * Return an array that can be used to redirect user to bank gate way.
     *
     * Return format is like this:
     * <code>
     * [
     *  'action'=>'https://bank.shaparak.ir/payment
     *  'method'=>"POST",
     *  'inputs'=>[
     *      'amount'=>100,
     *      'merchant'=>123,
     *      ...
     *  ]
     * ]
     * </code>
     * Or use below definition to redirection:
     * <code>
     * [
     *      'redirect'=>'https://redirect.address
     * ]
     * </code>
     *
     * @return array
     */
    public function redirectToBankFormData(): array
    {
        return [
            'redirect' => sprintf($this->getIdentityRedirectUrl(), $this->getAuthority())
        ];
    }

    /**
     * @inheritdoc
     */
    public function verifyTransaction(): GateInterface
    {
        $this->status = false;
        if ($this->dispatchRequest()) {
            $data = [
                'MerchantID' => $this->getIdentityMerchantCode(),
                'Amount' => $this->getAmount(false),
                'Authority' => $this->getAuthority()
            ];

            $this->request = $data;

            $client = new Client();
            $request = $client->createRequest()
                ->setMethod('post')
                ->setUrl($this->getIdentityVerifyRequestUrl())
                ->setContent(json_encode($data))
                ->setHeaders([
                    'Content-Type' => 'application/json'
                ])
                ->setData($data);
            /** @var \yii\httpclient\Response $response */
            $response = $request->send();
            $response = json_decode($response->getContent(), true);

            $this->response = $response;
            $this->statusCode = $response['Status'];

            if ($this->status == 100 or $this->status == 101) {
                $this->status = true;
                $this->setTrackingCode($response['RefID']);
            }
        }
        return $this;
    }

    /**
     * Return bank requests as array.
     *
     * @return mixed
     */
    public function getRequest(): array
    {
        return $this->request;
    }

    /**
     * Return bank response as array.
     *
     * @return mixed
     */
    public function getResponse(): array
    {
        return $this->response;
    }

    /**
     * Return status of pay request, verify or inquiry request.
     *
     * @return boolean
     */
    public function getStatus(): bool
    {
        return ($this->status == 100 or $this->status == 101) and ($this->status === true);
    }
}