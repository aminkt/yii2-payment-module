<?php
/**
 * Created by PhpStorm.
 * User: sagharmojdehi
 * Date: 12/13/17
 * Time: 10:26 AM
 */

namespace aminkt\payment\models\search;


use aminkt\payment\models\TransactionSession;
use yii\data\ActiveDataProvider;

/**
 * Search model for Transactions - payment module
 * @author Saghar Mojdehi <saghar.mojdehi@gmail.com>
 */
class TransactionSessionSearch extends TransactionSession
{


    public function rules()
    {
        return [
            [['orderId', 'psp', 'authority', 'amount', 'trackingCode', 'status',
                'type', 'userCardPan', 'userMobile', 'ip', 'updateAt', 'createAt'], 'safe'],
        ];
    }

    public $param;

    public function search($params)
    {
        $query = TransactionSession::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        $dataProvider->setSort( [
            'defaultOrder' => [
                'createAt' => SORT_DESC,
                'updateAt' => SORT_DESC,
            ]
        ]);


        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'orderId', $this->orderId])
            ->andFilterWhere(['like', 'psp', $this->psp])
            ->andFilterWhere(['like', 'authority', $this->authority])
            ->andFilterWhere(['like', 'amount', $this->amount])
            ->andFilterWhere(['like', 'trackingCode', $this->trackingCode])
            ->andFilterWhere(['like', 'status', $this->status])
            ->andFilterWhere(['like', 'type', $this->type])
            ->andFilterWhere(['like', 'userCardPan', $this->userCardPan])
            ->andFilterWhere(['like', 'userMobile', $this->userMobile])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'updateAt', $this->updateAt])
            ->andFilterWhere(['like', 'createAt', $this->createAt]);

        return $dataProvider;
    }
}