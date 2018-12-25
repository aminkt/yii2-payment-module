<?php

namespace aminkt\yii2\payment\controllers\backend;

use aminkt\yii2\payment\models\search\TransactionSessionSearch;
use yii\web\Controller;

/**
 * Default controller for the `payment` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     * @author Saghar Mojdehi <saghar.mojdehi@gmail.com>
     */
    public function actionIndex()
    {
        $searchModel = new TransactionSessionSearch();
        $dataProvider = $searchModel->search(\Yii::$app->request->queryParams);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel
        ]);
    }

}
