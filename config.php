<?php
return [
    'components' => [
        'payment' => [
            'class' => 'aminkt\payment\components\Payment',
            'callbackUr'=>['/payment/default/verify'],
            'gates'=>[
                \aminkt\payment\lib\Sep::$gateId => [
                    'class' => \aminkt\payment\lib\Sep::className(),
                    'identityData' => [
                        'MID' => '******',
                        'password' => '******',
                        'bankGatewayAddress' => 'https://sep.shaparak.ir/payment.aspx',
                        'webService' => 'https://sep.shaparak.ir/payments/referencepayment.asmx',
                    ]
                ],
                \aminkt\payment\lib\MellatGate::$gateId => [
                    'class' => \aminkt\payment\lib\MellatGate::className(),
                    'identityData'=>[
                        'terminalId' => '***',
                        'userName' => '***',
                        'password' => '***',
                        'payerId'=>0,
                        'webService'=>'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl',
                        'bankGatewayAddress'=>'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                    ]
                ],

            ],
        ],
    ],
    'params' => [
        // list of parameters
    ],
];