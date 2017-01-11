<?php
return [
    'components' => [
        'payment' => [
            'class' => 'payment\components\Payment',
            'callbackUr'=>['/payment/default/verify'],
            'gates'=>[
                \payment\lib\MellatGate::$gateId =>[
                    'class'=>\payment\lib\MellatGate::className(),
                    'identityData'=>[
                        'terminalId'=>'2149425',
                        'userName'=>'telbit85',
                        'password'=> '18993044',
                        'payerId'=>0,
                        'webService'=>'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl',
                        'bankGatewayAddress'=>'https://bpm.shaparak.ir/pgwchannel/startpay.mellat',
                    ]
                ],
                \payment\lib\BitPayirGate::$gateId =>[
                    'class'=>\payment\lib\BitPayirGate::className(),
                    'identityData'=>[
                        'api'=>'426c0b5263ac68b52c17be2d35ff67a5',
                        'bankPayReqAddress'=>'http://bitpay.ir/payment/gateway-send',
                        'bankGatewayAddress'=>'http://bitpay.ir/payment/gateway-',
                        'bankVerifyAddress'=>'http://bitpay.ir/payment/gateway-result-second',
                    ]
                ],
//                \payment\lib\PayirGate::$gateId =>[
//                    'class'=>\payment\lib\PayirGate::className(),
//                    'identityData'=>[
//                        'api'=>'426c0b5263ac68b52c17be2d35ff67a5',
//                        'bankPayReqAddress'=>'https://pay.ir/payment/send',
//                        'bankGatewayAddress'=>'https://pay.ir/payment/gateway',
//                        'bankVerifyAddress'=>'https://pay.ir/payment/verify',
//                    ]
//                ],
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