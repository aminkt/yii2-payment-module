<?php


namespace aminkt\yii2\payment\interfaces;

/**
 * Interface OrderInterface
 * Order model should extend from this OrderInterface.
 * Payment module will use this order model to initialize a payment request.
 * Every payment request should has a order model that you should implement it.
 *
 * @see https://github.com/aminkt/yii2-ordering-module If you need implement a ordering system.
 *
 *
 * @package aminkt\yii2\payment\interfaces
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
interface OrderInterface
{
    /**
     * Return payment amount.
     * This method should overwirte to calculate the amount of orderd needed to pay.
     * Consider a situation that user can pay order by vitrual credit. In this method please calculate remaining
     * money that user should pay.
     *
     * @return integer  Pay amount. Should return as an integer number in IR|TOMAN.
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getPayAmount();

    /**
     * Return order id.
     * This field should return primiary key of model order.
     *
     * @return mixed
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getId();
}