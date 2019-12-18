<?php
/**
 * Created by PhpStorm.
 * User: sagharmojdehi
 * Date: 12/13/17
 * Time: 10:26 AM
 */

namespace aminkt\yii2\payment\models\search;


use aminkt\yii2\payment\models\TransactionSession;
use yii\data\ActiveDataProvider;

/**
 * Search model for Transactions - payment module
 *
 * @author Saghar Mojdehi <saghar.mojdehi@gmail.com>
 */
class TransactionSessionSearch extends TransactionSession
{


    public $param;

    public function rules()
    {
        return [
            [['order_id', 'psp', 'authority', 'amount', 'tracking_code', 'status',
                'type', 'user_card_pan', 'user_mobile', 'ip', 'updated_at', 'created_at'], 'safe'],
        ];
    }

    /**
     * Search in transaction sessions.
     *
     * @param array $params Search params.
     * @param string $formName  Form name to load search params.
     *
     * @return \yii\data\ActiveDataProvider
     *
     * @author Amin Keshavarz <ak_1596@yahoo.com>
     */
    public function search($params, $formName = null)
    {
        $query = TransactionSession::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $dataProvider->setSort([
            'defaultOrder' => [
                'created_at' => SORT_DESC,
                'updated_at' => SORT_DESC,
            ]
        ]);


        if (!($this->load($params, $formName) && $this->validate())) {
            return $dataProvider;
        }

        $query->andWhere(['order_id' => $this->orderId])
            ->andFilterWhere(['like', 'psp', $this->psp])
            ->andFilterWhere(['like', 'authority', $this->authority])
            ->andFilterWhere(['like', 'amount', $this->amount])
            ->andFilterWhere(['like', 'tracking_code', $this->trackingCode])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'user_card_pan', $this->userCardPan])
            ->andFilterWhere(['like', 'user_mobile', $this->userMobile])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'update_at', $this->updateAt])
            ->andFilterWhere(['like', 'create_at', $this->createAt]);

        return $dataProvider;
    }
}