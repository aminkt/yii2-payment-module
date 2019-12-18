<?php


namespace aminkt\yii2\payment\interfaces;

/**
 * Interface GateLogInterface
 * Implement this interface to enable log features in gate object.
 *
 * @package aminkt\yii2\payment\interfaces
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
interface GateLogInterface
{
    /**
     * Return bank requests as array.
     * This method using to loging input an output requests.
     *
     * @see \aminkt\yii2\payment\gates\AbstractGate::getResponse()
     *
     * @return array
     */
    public function getRequest(): array;

    /**
     * Return bank response as array.
     * This method using to loging input an output requests.
     *
     * @see \aminkt\yii2\payment\gates\AbstractGate::getRequest()
     *
     * @return array
     */
    public function getResponse(): array;
}