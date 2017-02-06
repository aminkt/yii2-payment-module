<?php
/** @var $view \yii\web\View */
/** @var $data array */
?>
<div class="<?= $this->context->action->id ?>" style="text-align: center; padding: 50px;">
    <?php if($data) : ?>
        <h1>لطفا کمی صبر کنید</h1>
    <?php else: ?>
        <h1>خطا در برسی اطلاعات</h1>
    <?php endif; ?>
    <br>
    <br>
    <p>
        <?php if($data) : ?>
        درحال ارسال اطلاعات به بانک ...
        <?php else: ?>
            اطلاعات ارسالی برای بانک معتبر نیست.
        <?php endif; ?>
    </p>
    <br>
    <br>
    <br>
    <br>
    <p>
        درصورتی که به صفحه بانک هدایت نشدید این مورد را با پشتیبانی سایت در میان بگذارید.
    </p>
</div>
<?php if($data) : ?>
<script>
    var form = document.createElement("form");
    form.setAttribute("method", "POST");
    form.setAttribute("action", "<?= $data['bankUrl'] ?>");
    form.setAttribute("target", "_self");
    var hiddenField;
    <?php if (isset($data['post']) and count($data['post'])>0) : ?>
        <?php foreach ($data['post'] as $key=>$value) : ?>
            hiddenField = document.createElement("input");
            hiddenField.setAttribute("name", "<?= $key ?>");
            hiddenField.setAttribute("value", "<?= $value ?>");
            form.appendChild(hiddenField);
        <?php endforeach; ?>
    <?php endif; ?>
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
</script>
<?php endif; ?>