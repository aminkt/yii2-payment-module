<?php

namespace aminkt\payment\lib;

/**
 * Class ZarinPal gate of Zarinpal
 *
 * @method string getIdentityMID() Return terminal id.
 * @method string getIdentityPassword() Return Password.
 * @method string getIdentityWebService()   Return webservice address.
 * @method string getIdentityBankGatewayAddress()   Return bank gateway address.
 *
 * @package payment\lib
 */
class ZarinPal extends AbstractGate
{
    public static $transBankName = 'ZatinPal';
    public static $gateId = 'Zarin96';

    /**
     * Dispatch payment response from bank.
     *
     * @return boolean
     */
    public function dispatchRequest()
    {
        // TODO: Implement dispatchRequest() method.
    }

    /**
     * Prepare data and config gate for payment.
     *
     * @throws \aminkt\payment\exceptions\ConnectionException   Connection failed.
     *
     * @return array
     * Return data to redirect user to bank.
     */
    public function payRequest()
    {
        // TODO: Implement payRequest() method.
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
        // TODO: Implement redirectToBankFormData() method.
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
        // TODO: Implement verifyTransaction() method.
    }

    /**
     * Return bank requests as array.
     *
     * @return mixed
     */
    public function getRequest()
    {
        // TODO: Implement getRequest() method.
    }

    /**
     * Return bank response as array.
     *
     * @return mixed
     */
    public function getResponse()
    {
        // TODO: Implement getResponse() method.
    }

    /**
     * Return status of pay request, verify or inquiry request.
     *
     * @return boolean
     */
    public function getStatus()
    {
        // TODO: Implement getStatus() method.
    }
}