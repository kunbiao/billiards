<?php
namespace frontend\models;

use Yii;

/**
 * This is the model class for table "ground".
 *
 * @property int $id 场地id
 * @property string $name 场地名字
 */
class Ground extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ground';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
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
            'name' => 'Name',
        ];
    }
}
