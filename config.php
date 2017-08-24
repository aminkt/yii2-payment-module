<?php
return [
    'components' => [
        'payment' => [
            'class' => 'aminkt\payment\components\Payment',
            'callbackUr'=>['/payment/default/verify'],
            'gates'=>[
                \aminkt\payment\lib\MellatGate::$gateId => [
                    'class' => \aminkt\payment\lib\MellatGate::className(),
                    'identityData'=>[
                        'terminalId'=>'2149425',
                        'userName'=>'telbit85',
                        'password'=> '18993044',
                        'payerId'=>0,
                        'webService'=>'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl',
                        'bankGatewayAddress'=>'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                    ]
                ],
//                \payment\lib\SamanGate::$gateId =>[
//                    'class'=>\payment\lib\SamanGate::className(),
//                    'identityData'=>[
//                        'MID'=>'10152019',
//                        'password'=>'1223661',
//                        'bankGatewayAddress'=>'https://sep.shaparak.ir/Payment.aspx',
//                        'webService'=>'https://sep.shaparak.ir/payments/referencepayment.asmx?WSDL',
//                    ]
//                ],
            ],
        ],
    ],
    'params' => [
        // list of parameters
    ],
];