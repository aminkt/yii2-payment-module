How to install this module:

Step1: First add flowing codes into project `composer.json`

```
"repositories": [
    {
        "type": "gitlab",
        "url": "https://gitlab.com/aminkt/yii2-payment-module"
    }
],
```

Then add flowing line to require part of `composer.json` :
```
"aminkt/yii2-payment-module": "*",
```

And after that run bellow command in your composer :
```
Composer update aminkt/yii2-payment-module
```

Step3: Add flowing lines in your application backend config:

```
'payment' => [
    'class' => 'aminkt\payment\Payment',
    'controllerNamespace' => 'aminkt\payment\controllers\backend',
],
```

Step4: Add flowing lines in your application frontend config:

```
'payment' => [
    'class' => 'aminkt\payment\Payment',
    'controllerNamespace' => 'aminkt\payment\controllers\frontend',
],
```

---
**Database Migrations**

Before usage this extension, we'll also need to prepare the database.

```
php yii migrate --migrationPath=@vendor/aminkt/yii2-payment-module/migrations
```

---
Usage:
---
In your code when you want create a payment request use below code:
```php
$payment = \aminkt\payment\Payment::getInstance()->payment;
$data = $payment->payRequest(100, time());
// $data is an array that hold a form information that you should send to bank gateway
if(is_array($data) and array_key_exists('redirect', $data) and isset($data['redirect'])){
    return $this->redirect($data['redirect']);
} else {
    return $this->render('send', [
        'data'=>$data
    ]);
}
```

When user paid money he will redirect in a page in you site that you defined.
By default user will redirect to `/payment/default/verify` route.

For changing default call back page use below code:

```php
$payment = \aminkt\payment\Payment::getInstance()->payment;
$payment->callbackUr = ['/your-controller/your-action']; // callBack give an array defined a route.
```

In your verify page use below code to verify payment:
```php
$verify = \aminkt\payment\Payment::getInstance()->payment->verify();
return $this->render('verify', [
    'verify'=>$verify,
]);
```
`$verify` is `false` if verify action become failed and otherwise return an `\aminkt\payment\lib\AbstractGate` object.
> `\aminkt\payment\lib\AbstractGate` is main class class of gateway objects. 
>   
> It's contain gateway data.

---
Reports:
---

In you backend panel you can use module routes to see various reports include Transaction sessions, payment logs, Inquiry requests and bank shortage data.

---
Structure of tables and classes:
---
![Data base scheme](structure.png)