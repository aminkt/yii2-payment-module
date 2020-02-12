<?php
/** @var $this \yii\web\View */
/** @var $verify boolean|aminkt\payment\lib\AbstractGate */

$this->title = "نتیجه تراکنش - تل بیت";
?>
<div class="<?= $this->context->action->id ?>" style="text-align: center; padding: 50px; color: #fff">
    <?php if($verify) : ?>
        <h1>خرید با موفقیت انجام شد</h1>
    <?php else: ?>
        <h1>خرید شما موفق نبود</h1>
    <?php endif; ?>
    <br>    <br>
    <?php if($verify) : ?>
        <p>
            برای اطلاعات بیشتر با پشتیبانی تماس بگیرید
        </p>
    <?php else: ?>
        <p>
            <?php foreach (\aminkt\yii2\payment\components\Payment::getErrors() as $error) : ?>
                <?= $error['code'] ?> : <?= $error['message'] ?>
                <br>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>

    <br>    <br>    <br>    <br>
</div>
