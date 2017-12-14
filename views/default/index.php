<?php
/**
 * @var $dataProvider
 * @var \aminkt\payment\models\search\TransactionSessionSearch $searchModel
 */
?>


    <?php
    echo  \yii\grid\GridView::widget([
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
                'attribute' => 'type'
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
                'attribute' => 'updateAt'
            ],
            [
                'attribute' => 'createAt'
            ],
        ]
    ]);
    ?>