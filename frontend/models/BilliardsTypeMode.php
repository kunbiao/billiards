<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "billiards_type_mode".
 *
 * @property int $id 比赛模式和比赛类型的关联
 * @property int $type_id 比赛类型Id
 * @property int $mode_id 模式Id
 */
class BilliardsTypeMode extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'billiards_type_mode';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type_id', 'mode_id'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type_id' => 'Type ID',
            'mode_id' => 'Mode ID',
        ];
    }
}
