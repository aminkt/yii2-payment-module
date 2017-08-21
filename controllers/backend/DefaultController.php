<?php

namespace aminkt\payment\controllers\backend;

use yii\web\Controller;

/**
 * Default controller for the `payment` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
}
