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
     * Add an error to error collection/
     *
     * @param            $message string
     * @param int|string $code    string
     * @param bool       $countUpBlockCounter
     */
    public function addError($message, $code = 0, $countUpBlockCounter = false)
    {
        \Yii::error($message, $code);
        static::$errors[] = [
            'message' => $message,
            'code' => $code,
        ];
        if ($countUpBlockCounter) {
            $this->incrementBlockCounter();
        }
    }

    /**
     * Increment counter of block.
     */
    public function incrementBlockCounter()
    {
        if (YII_ENV_DEV or $this->enableByPass)
            return;

        $counter = $this->getBlockCounter();
        $counter++;

        \Yii::$app->getCache()->set($this->getBlockCounterKey(), $counter);
    }

    /**
     * Clean errors collection
     *
     * @param bool $resetBlockCounter
     */
    public function cleanErrors($resetBlockCounter = false)
    {
        static::$errors = [];
        if ($resetBlockCounter) {
            \Yii::$app->getCache()->delete($this->getBlockCounterKey());
        }
    }

    /**
     * Reset counter of block.
     */
    public function resetBlockCounter()
    {
        $blockCounter = $this->getBlockCounter();
        if ($blockCounter > 0) {
            \Yii::$app->getCache()->delete($this->getBlockCounterKey());
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
     * Check if customer is blocked because of bad behaviors or not.
     *
     * @return bool
     */
    public function isBlocked()
    {
        // Check if byPass enabled, shutdown block service.
        if ($this->enableByPass) {
            return false;
        }

        if (\Yii::$app->getCache()->exists($this->getBlockKey())) {
            return true;
        }
        if ($this->getBlockCounter() >= $this->alowedCredentialErrors) {
            $this->blockPaymentServices();
            return true;
        }
        return false;
    }

    /**
     * Return block key for every user.
     *
     * @return string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getBlockKey()
    {
        $ip = \Yii::$app->getRequest()->getUserIP();
        $key = base64_encode($ip);
        return static::CACHE_PAYMENT_BLOCKED . "." . $key;
    }

    /**
     * Return block counter cache key.
     *
     * @return string
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function getBlockCounterKey() {
        $ip = \Yii::$app->getRequest()->getUserIP();
        $key = base64_encode($ip);
        $key = static::CACHE_PAYMENT_BLOCK_ERRORS_COUNT . "." . $key;
        return $key;
    }

    /**
     * Return counter of block for one user..
     *
     * @return integer
     */
    public function getBlockCounter()
    {
        $count = \Yii::$app->getCache()->get($this->getBlockCounterKey());
        if (!$count) {
            return 0;
        }
        return $count;
    }

    /**
     * Block user.
     */
    public function blockPaymentServices()
    {
        if ($this->blockTime) {
            $blockTime = time() + $this->blockTime;
        } else {
            $blockTime = null;
        }
        \Yii::$app->getCache()->set($this->getBlockKey(), $this->getBlockCounter(), $blockTime);
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
     * Decrypt token and return bank class name.
     *
     * @param string $token Bank token.
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
            $payload = json_decode($payload, true);

            if (isset($payload['expire_in']) and time() > $payload['expire_in']) {
                throw new SecurityException("Token expired.");
            }

            return $payload['gate'];
        }

        throw new SecurityException("Token is not valid.");
    }
}