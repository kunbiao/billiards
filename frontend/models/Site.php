<?php
namespace frontend\models;

use Yii;

/**
 * This is the model class for table "site".
 *
 * @property string $id
 * @property int $type 场馆类型，1：网球
 * @property string $name 场馆名称
 * @property string $address 场馆地址
 * @property string $phone_number 场馆电话
 * @property string $portrait 场馆头像
 * @property int $status 状态
 * @property string $create_time
 * @property string $update_time
 */
class Site extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'site';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['name'], 'string', 'max' => 64],
            [['address'], 'string', 'max' => 255],
            [['phone_number'], 'string', 'max' => 20],
            [['portrait'], 'string', 'max' => 250],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'name' => 'Name',
            'address' => 'Address',
            'phone_number' => 'Phone Number',
            'portrait' => 'Portrait',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
