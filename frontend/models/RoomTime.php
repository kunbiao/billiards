<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "room_time".
 *
 * @property int $id 线上模式使用时间
 * @property int $bet_id 赌注id
 * @property int $mode_id 模式id
 * @property int $use_time 使用时间
 */
class RoomTime extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'room_time';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['bet_id', 'mode_id', 'use_time'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'bet_id' => 'Bet ID',
            'mode_id' => 'Mode ID',
            'use_time' => 'Use Time',
        ];
    }
}
