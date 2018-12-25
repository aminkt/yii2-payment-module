<?php


namespace aminkt\yii2\payment\traits;
use aminkt\exceptions\SecurityException;

/**
 * Trait SecurityTrait
 *
 * This trait used in component `\aminkt\yii2\payment\components\Payment`.
 *
 * @see     \aminkt\yii2\payment\components\Payment
 *
 * @package aminkt\yii2\payment\traits
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
trait SecurityTrait
{


    /**
     * Generate and set payment token.
     *
     * @return string
     */
    public static function generatePaymentToken()
    {
        $token = \Yii::$app->getSecurity()->generateRandomString(10);
        \Yii::$app->getSession()->set(self::SESSION_NAME_OF_TOKEN, $token);
        return $token;
    }

    /**
     * Encrypt bank name to define correct gate in callback.
     *
     * @param $bankName
     *
     * @return string
     */
    public static function encryptBankName($bankName)
    {
        return $bankName;
    }

    /**
     * Check if customer is blocked because of bad behaviors or not.
     *
     * @return bool
     */
    public static function isBlocked()
    {
        $cookiesRequest = \Yii::$app->request->cookies;
        if ($cookiesRequest->has(static::COOKIE_PAYMENT_BLOCKED)) {
            return true;
        }
        if (static::getBlockCounter() >= 5) {
            static::blockPaymentServices();
            return true;
        }
        return false;
    }

    /**
     * Return counter of block.
     *
     * @return integer
     */
    public static function getBlockCounter()
    {
        $cookiesRequest = \Yii::$app->request->cookies;
        return $cookiesRequest->getValue(static::COOKIE_PAYMENT_MUCH_ERROR, 0);
    }

    /**
     * Block user
     */
    public static function blockPaymentServices()
    {
        $cookiesResponse = \Yii::$app->response->cookies;
        $cookiesResponse->add(new Cookie([
            'name' => static::COOKIE_PAYMENT_BLOCKED,
            'value' => static::getBlockCounter(),
            'expire' => time() + 60 * 60 * 24
        ]));
    }

    /**
     * Add an error to error collection/
     *
     * @param            $message string
     * @param int|string $code    string
     * @param bool       $countUpBlockCounter
     */
    public static function addError($message, $code = 0, $countUpBlockCounter = false)
    {
        \Yii::error($message, $code);
        static::$errors[] = [
            'message' => $message,
            'code' => $code,
        ];
        if ($countUpBlockCounter) {
            static::incrementBlockCounter();
        }
    }

    /**
     * Increment counter of block.
     */
    public static function incrementBlockCounter()
    {
        if (YII_ENV_DEV)
            return;

        $cookiesResponse = \Yii::$app->response->cookies;
        $cookiesRequest = \Yii::$app->request->cookies;
        $counter = $cookiesRequest->getValue(static::COOKIE_PAYMENT_MUCH_ERROR, 0);
        $counter++;
        $cookiesResponse->add(new Cookie([
            'name' => static::COOKIE_PAYMENT_MUCH_ERROR,
            'value' => $counter,
            'expire' => time() + 60 * 60 * 3
        ]));
    }

    /**
     * Clean errors collection
     *
     * @param bool $resetBlockCounter
     */
    public static function cleanErrors($resetBlockCounter = false)
    {
        static::$errors = [];
        if ($resetBlockCounter) {
            static::resetBlockCounter();
        }
    }

    /**
     * Reset counter of block.
     */
    public static function resetBlockCounter()
    {
        $cookiesResponse = \Yii::$app->response->cookies;
        $cookiesRequest = \Yii::$app->request->cookies;
        if ($cookiesRequest->has(static::COOKIE_PAYMENT_MUCH_ERROR)) {
            $cookiesResponse->add(new Cookie([
                'name' => static::COOKIE_PAYMENT_MUCH_ERROR,
                'value' => 0,
                'expire' => time() + 60 * 60 * 3
            ]));
        }
    }

    /**
     * Return errors as array
     *
     * @return array
     */
    public static function getErrors()
    {
        return static::$errors;
    }

    /**
     * Return security key.
     *
     * @return string
     */
    public function getSecureKey()
    {
        if ($this->encryptKey) {
            return $this->encryptKey;
        }

        throw new \aminkt\exceptions\SecurityException("Encrypt key is not valid.");
    }

    /**
     * Generate secure token to verify and validate payment.
     *
     * @param string $gateName Gate name.
     *
     * @return string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function generateToken($gateName)
    {
        $payload = [
            'gate' => $gateName,
        ];

        if ($this->bankTimeout) {
            $payload['expire_in'] = time() + $this->bankTimeout;
        }

        $base64 = base64_encode(json_encode($payload));

        $token = \Yii::$app->getSecurity()->encryptByKey($base64, $this->getSecureKey());

        return $token;
    }


    /**
     * Decrypt token and return bank class name.
     *
     * @param string    $token  Bank token.
     *
     * @return string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     *
     * @throws \aminkt\exceptions\SecurityException If token is not valid.
     */
    public function decryptToken($token)
    {
        $payload = \Yii::$app->getSecurity()->decryptByKey($token, $this->getSecureKey());

        if ($payload) {
            $payload = base64_decode($payload);
            $payload = json_decode($payload);

            if(isset($payload['expire_in']) and time() > $payload['expire_in']) {
                throw new SecurityException("Token expired.");
            }

            return $payload['gate'];
        }

        throw new SecurityException("Token is not valid.");
    }
}