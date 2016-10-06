<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[DaemonJob]].
 *
 * @see DaemonJob
 */
class DaemonJobQuery extends \yii\db\ActiveQuery
{
    public function new()
    {
        $this->andWhere(['status' => 'new']);
        return $this;
    }

    /**
     * @inheritdoc
     * @return DaemonJob[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return DaemonJob|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}