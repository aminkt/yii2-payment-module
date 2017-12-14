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
                'label' => 'Order id',
                'attribute' => 'orderId'
            ],
            [
                'label' => 'PSP',
                'attribute' => 'psp'
            ],
            [
                'label' => 'Authority',
                'attribute' => 'authority'
            ],
            [
                'label' => 'Amount',
                'attribute' => 'amount'
            ],
            [
                'label' => 'TrackingCode',
                'attribute' => 'trackingCode'
            ],
            [
                'label' => 'Type',
                'attribute' => 'type'
            ],
            [
                'label' => 'User Card Pan',
                'attribute' => 'userCardPan'
            ],
            [
                'label' => 'User Mobile',
                'attribute' => 'userMobile'
            ],
            [
                'label' => 'ip',
                'attribute' => 'ip'
            ],
            [
                'label' => 'Update At',
                'attribute' => 'updateAt'
            ],
            [
                'label' => 'Create At',
                'attribute' => 'createAt'
            ],
        ]
    ]);
    ?>