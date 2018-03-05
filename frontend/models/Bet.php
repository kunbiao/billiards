<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "bet".
 *
 * @property int $id
 * @property int $number 金额
 */
class Bet extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bet';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['number','use_time'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'number' => 'Number',
        ];
    }
}
