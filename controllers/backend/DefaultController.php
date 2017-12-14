<?php

namespace aminkt\payment\controllers\backend;

use aminkt\payment\models\search\TransactionSessionSearch;
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
