<?php
/**
 * @var $dataProvider
 * @var \aminkt\payment\models\search\TransactionSessionSearch $searchModel
 */
?>


<?php
echo \yii\grid\GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        ['class' => 'yii\grid\SerialColumn'],
        [
            'attribute' => 'orderId'
        ],
        [
            'attribute' => 'psp'
        ],
        [
            'attribute' => 'authority'
        ],
        [
            'attribute' => 'amount'
        ],
        [
            'attribute' => 'trackingCode'
        ],
        [
            'attribute' => 'type',
            'value' => function ($model) {
                return $model::getLabel($model->type, 'type');
            },
            'filter' => [
                \aminkt\yii2\payment\models\TransactionSession::TYPE_WEB_BASE => 'اینترنتی',
                \aminkt\yii2\payment\models\TransactionSession::TYPE_CART_TO_CART => 'کارت به کارت',
            ],
        ],
        [
            'attribute' => 'status',
            'value' => function ($model) {
                return $model::getLabel($model->status, 'status');
            },
            'filter' => [
                \aminkt\yii2\payment\models\TransactionSession::STATUS_NOT_PAID => 'پرداخت نشده',
                \aminkt\yii2\payment\models\TransactionSession::STATUS_PAID => 'پرداخت شده',
                \aminkt\yii2\payment\models\TransactionSession::STATUS_FAILED => 'ناموفق',
                \aminkt\yii2\payment\models\TransactionSession::STATUS_INQUIRY_PROBLEM => 'مغایرت بانکی',
            ],
        ],
        [
            'attribute' => 'userCardPan'
        ],
        [
            'attribute' => 'userMobile'
        ],
        [
            'label' => 'ip',
            'attribute' => 'ip'
        ],
        [
            'attribute' => 'updateAt',
            'value' => function ($model) {
                return \Yii::$app->getFormatter()->asDatetime($model->updateAt, null);
            }
        ],
        [
            'attribute' => 'createAt',
            'value' => function ($model) {
                return \Yii::$app->getFormatter()->asDatetime($model->createAt, null);
            }
        ],
    ]
]);
?>