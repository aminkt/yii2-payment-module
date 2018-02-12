<?php

namespace aminkt\payment\lib;

use aminkt\payment\components\Payment;
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
    public static $transBankName = 'ZatinPal';
    public static $gateId = 'Zarin96';

    public $status = false;
    private $request;
    private $response;

    /**
     * Dispatch payment response from bank.
     *
     * @return boolean
     */
    public function dispatchRequest()
    {
        $this->setOrderId($_GET['oi']);
        if (isset($_GET['Authority'])) {
            $this->setAuthority($_GET['Authority']);
            return true;
        }
        return false;
    }

    /**
     * Prepare data and config gate for payment.
     *
     * @throws \aminkt\payment\exceptions\ConnectionException   Connection failed.
     *
     * @return array|boolean
     * Return data to redirect user to bank.
     */
    public function payRequest()
    {
        $data = [
            'MerchantID' => $this->getIdentityMerchantCode(),
            'Amount' => $this->getAmount(false),
            'CallbackURL' => $this->getCallbackUrl(),
            'Description' => " "
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

        $this->status = $response['Status'];

        if (isset($response['Authority'])) {
            $this->setAuthority($response['Authority']);
            return true;
        }

        return false;
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
    public function redirectToBankFormData()
    {
        return [
            'redirect' => sprintf($this->getIdentityRedirectUrl(), $this->getAuthority())
        ];
    }

    /**
     * Verify Transaction if its paid. this method should call in callback from bank.
     *
     * @throws \aminkt\payment\exceptions\VerifyPaymentException
     * @throws \aminkt\payment\exceptions\ConnectionException
     *
     * @return AbstractGate|boolean
     */
    public function verifyTransaction()
    {
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
            $this->status = $response['Status'];

            if ($this->status == 100 or $this->status == 101) {
                $this->setTrackingCode($response['RefID']);
                return $this;
            }
        }
        return false;
    }

    /**
     * Return bank requests as array.
     *
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Return bank response as array.
     *
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Return status of pay request, verify or inquiry request.
     *
     * @return boolean
     */
    public function getStatus()
    {
        return ($this->status == 100 or $this->status == 101);
    }

    /**
     * @inheritdoc
     */
    public function setCallbackUrl($callbackUrl)
    {
        $bank = Payment::encryptBankName(static::$gateId);
        $token = Payment::generatePaymentToken();
        $callbackUrl['bc'] = $bank;
        $callbackUrl['token'] = $token;
        $callbackUrl['oi'] = $this->getOrderId();
        $this->callbackUrl = \Yii::$app->getUrlManager()->createAbsoluteUrl($callbackUrl);
        return $this;
    }
}