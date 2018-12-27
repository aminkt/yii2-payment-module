<?php


namespace aminkt\yii2\payment\interfaces;

/**
 * Interface GateInterface
 * Define methods that a valid gate should implement.
 *
 * @package aminkt\yii2\payment\interfaces
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
interface GateInterface
{
    /**
     * Dispatch payment response from bank.
     * Return false if cant dispatch request or return true to show that needed data dispatched from request.
     *
     * @return bool
     */
    public function dispatchRequest(): bool;

    /**
     * Connect to bank web service to check gate is available or not.
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     *
     * @throws \aminkt\exceptions\ConnectionException   When connection become failed.
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function connect(): self;

    /**
     * Prepare data and config gate for payment.
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function payRequest(): GateInterface;

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
    public function redirectToBankFormData(): array;

    /**
     * Verify Transaction if it's paid or return false.
     * This method should call in callback from bank.
     *
     * @throws \aminkt\exceptions\VerifyPaymentException
     * @throws \aminkt\exceptions\ConnectionException
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function verifyTransaction(): GateInterface;

    /**
     * If for any reason you need check transaction status, this method ask again status of transaction from bank.
     * >**note: This method may not implement in all bank gates.**
     *
     * @throws \aminkt\exceptions\NotImplementedException
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function inquiryTransaction(): GateInterface;

    /**
     * Get callback url.
     * Should return an string that represent callback url.
     *
     * @return string
     */
    public function getCallbackUrl(): string;

    /**
     * Set callback url.
     * Input should be an array in yii2 routing array format or you can just put here the url.
     *
     * @see \yii\helpers\Url::to()
     * To know more about array routing format.
     *
     * @param array|string $callbackUrl
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function setCallbackUrl($callbackUrl): GateInterface;


    /**
     * Return status of pay request, verify or inquiry request.
     *
     * @return boolean
     */
    public function getStatus(): bool;

    /**
     * Return amount.
     *
     * @param bool $rial Return amount as rial. Set true to return amount in IRR Rial.
     *
     * @return int
     */
    public function getAmount(bool $rial = false): int;

    /**
     * Set transaction amount.
     *
     * @param int $amount Amount of transaction.
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     *
     * @throws \aminkt\exceptions\InvalidAmountException When amount is invalid.
     */
    public function setAmount(int $amount): GateInterface;

    /**
     * Return transaction authority.
     *
     * @return string
     */
    public function getAuthority(): string;

    /**
     * Set transaction Authority.
     *
     * @param string $authority
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function setAuthority(string $authority): GateInterface;

    /**
     * Return order id.
     *
     * Be care full. If application be in dev env and bypass is enabled test data will return.
     *
     * @return string
     */
    public function getOrderId(): string;

    /**
     * Set order id.
     *
     * @param string $orderId
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function setOrderId($orderId): GateInterface;

    /**
     * Return transaction tracking code from bank.
     * This value can be used to tracking transaction from bank.
     *
     * @return string
     */
    public function getTrackingCode(): string;

    /**
     * Set transaction tracking code.
     * This value can be used to tracking transaction from bank.
     *
     * @param string $trackingCode
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function setTrackingCode(string $trackingCode): GateInterface;

    /**
     * Return user card pan if setted.
     *
     * @return null|string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getCardPan(): ?string;

    /**
     * Set user card pan.
     *
     * @param string $cardPan
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function setCardPan(string $cardPan): GateInterface;

    /**
     * Return user card hash if setted.
     *
     * @return null|string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getCardHash(): ?string;

    /**
     * Set user card hash.
     *
     * @param string $cardHash
     *
     * @return \aminkt\yii2\payment\interfaces\GateInterface
     */
    public function setCardHash(string $cardHash): GateInterface;
}