<?php


namespace aminkt\yii2\payment\traits;

use aminkt\yii2\payment\models\TransactionInquiry;
use aminkt\yii2\payment\models\TransactionLog;
use aminkt\yii2\payment\models\TransactionSession;

/**
 * Trait LogTrait
 *
 * This trait used in component `\aminkt\yii2\payment\components\Payment`.
 *
 * This trait will save payment data into database.
 *
 * @see     \aminkt\yii2\payment\components\Payment
 *
 * @package aminkt\yii2\payment\traits
 *
 * @author  Amin Keshavarz <ak_1596@yahoo.com>
 */
trait LogTrait
{

    /**
     * List of errors.
     *
     * @var array $errors
     */
    protected static $errors = [];

    /**
     * Save transaction data in db when verify request send and return true if its work correctly.
     *
     * @param $gate AbstractGate
     *
     * @throws \aminkt\payment\exceptions\SecurityException
     *
     * @return bool
     */
    public function saveVerifyDataIntoDatabase($gate)
    {
        /**
         * Throw an verify event. can be used in kernel to save and modify transactions.
         */
        $transactionSession = TransactionSession::findOne($gate->getOrderId(false));

        /**
         * Save transactions logs.
         */
        self::saveLogData($transactionSession, $gate, TransactionLog::STATUS_PAYMENT_VERIFY);

        /**
         * Check transaction correctness.
         */
        if ($transactionSession->status == TransactionSession::STATUS_PAID) {
            throw new SecurityException("This transaction paid before.");
            // or return false and track in verify method.
            return false;
        }

        /**
         * Update transactionSession data.
         */
        $transactionSession->userCardHash = $gate->getCardHash();
        $transactionSession->userCardPan = $gate->getCardPan();
        $transactionSession->trackingCode = $gate->getTrackingCode();
        if ($gate->getStatus()) {
            $transactionSession->status = TransactionSession::STATUS_PAID;
        } else {
            $transactionSession->status = TransactionSession::STATUS_FAILED;
        }

        if (!$transactionSession->save()) {
            \Yii::error($transactionSession->getErrors(), self::className());
            throw new \RuntimeException('Can not save transaction session data.', 12);
        } else {
            /**
             * Create an inquiry request for valid payments.
             */
            $inquiryRequest = new TransactionInquiry();
            $inquiryRequest->sessionId = $transactionSession->id;
            $inquiryRequest->status = TransactionInquiry::STATUS_INQUIRY_WAITING;
            $inquiryRequest->save(false);
        }

        /**
         * Throw an verify event. can be used in kernel to save and modify transactions.
         */
        $event = new PaymentEvent();
        $event->setGate($gate)
            ->setStatus($gate->getStatus())
            ->setTransactionSession($transactionSession);
        \Yii::$app->trigger(\aminkt\payment\Payment::EVENT_PAYMENT_VERIFY, $event);
        return true;
    }


    /**
     * Save transaction data in db when inquiry request send and return true if its work correctly.
     *
     * @param AbstractGate       $gate
     * @param TransactionInquiry $inquiryModel
     *
     * @return bool
     */
    public function saveInquiryDataIntoDatabase($gate, $inquiryModel)
    {
        /**
         * Save transactions logs.
         */
        self::saveLogData($inquiryModel->transactionSession, $gate, TransactionLog::STATUS_PAYMENT_INQUIRY);

        if ($gate->getStatus()) {
            $inquiryModel->status = TransactionInquiry::STATUS_INQUIRY_SUCCESS;
        } else {
            $inquiryModel->status = TransactionInquiry::STATUS_INQUIRY_FAILED;
        }

        if (!$inquiryModel->save()) {
            \Yii::error($inquiryModel->getErrors(), self::className());
            throw new \RuntimeException('Can not save transaction inquiry data.', 12);
        }

        /**
         * Throw an verify event. can be used in kernel to save and modify transactions.
         */
        $event = new PaymentEvent();
        $event->setGate($gate)
            ->setStatus($gate->getStatus())
            ->setTransactionInquiry($inquiryModel)
            ->setTransactionSession($inquiryModel->transactionSession);
        \Yii::$app->trigger(\aminkt\payment\Payment::EVENT_PAYMENT_INQUIRY, $event);
        return true;
    }


    /**
     * Save payment data in db when pay request send and return true if its work correctly.
     *
     * @param \aminkt\yii2\payment\gates\AbstractGate $gate Gate object.
     * @param \aminkt\yii2\payment\interfaces\OrderInterface       $order   Order model.
     * @param string       $description
     *
     * @return TransactionSession
     */
    public function savePaymentDataIntoDatabase($gate, $order, $description)
    {
        // Create transaction session data.
        $transactionSession = new TransactionSession([
            'authority' => $gate->getAuthority(),
            'order_id' => $gate->getOrderId(),
            'psp' => $gate::className(),
            'amount' => $order->getPayAmount(),
            'description' => Html::encode($description),
            'status' => TransactionSession::STATUS_NOT_PAID,
            'type' => TransactionSession::TYPE_WEB_BASE,
            'ip' => \Yii::$app->getRequest()->getUserIP()
        ]);

        if ($transactionSession->save()) {
            /**
             * Set transaction session id as payment order id.
             * Actual order id can be access from database later.
             **/
            $gate->setOrderId($transactionSession->id);

            /**
             * Save transactions logs.
             */
            self::saveLogData($transactionSession, $gate, TransactionLog::STATUS_PAYMENT_REQ);


            $event = new PaymentEvent();
            $event->setGate($gate)
                ->setStatus($gate->getStatus())
                ->setTransactionSession($transactionSession);
            \Yii::$app->trigger(\aminkt\yii2\payment\Payment::BEFORE_PAYMENT_REQUEST, $event);

            return $transactionSession;
        }

        \Yii::error($transactionSession->getErrors(), self::className());
        throw new \RuntimeException("Can not saving data into database.", 10);
    }

    /**
     * Save transactions logs.
     *
     * @param \aminkt\payment\models\TransactionSession $transactionSession
     * @param \aminkt\payment\lib\AbstractGate          $gate
     * @param string                                    $status
     *
     * @return void
     */
    public static function saveLogData($transactionSession, $gate, $status = TransactionLog::STATUS_UNKNOWN)
    {
        $log = new TransactionLog();
        $log->sessionId = $transactionSession->id;
        $log->bankDriver = $gate::className();
        $log->status = $status;
        $log->request = json_encode($gate->getRequest());
        $log->response = json_encode($gate->getResponse());
        $log->ip = \Yii::$app->getRequest()->getUserIP();
        $log->save(false);
    }

    /**
     * Update transaction session data.
     *
     * @param TransactionSession    $session
     * @param $col
     * @param $value
     *
     * @return TransactionSession
     *
     * @throws NotFoundHttpException
     */
    private function updatePaymentDataInDatabase($session, $col, $value)
    {
        $session->$col = $value;

        if ($session->save()) {
            return $session;
        }

        throw new InvalidAmountException("Cant save data into database.");
    }
}