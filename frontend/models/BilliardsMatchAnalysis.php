<?php
namespace frontend\models;

use Yii;
use yii\db\Exception;

/**
 * This is the model class for table "billiards_match_analysis".
 *
 * @property int $id 用户比赛详情
 * @property int $match_id 比赛id
 * @property int $user_id 用户id
 * @property int $score 用户得分
 * @property int $liangan 连杆数
 * @property int $foul 犯规数
 * @property int $goal 进球数
 * @property int $create_time 创建时间
 * @property int $update_time 修改时间
 * @property int $type 0为正在比赛 1为比赛结束
 * @property int $status 0为失败 1为胜利
 */
class BilliardsMatchAnalysis extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'billiards_match_analysis';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['statistics','access_token'], 'string', 'max' => 255],
            [['match_id', 'user_id'], 'required'],
            [['match_id','mode_id','flee','duration', 'user_id', 'score', 'liangan', 'foul', 'goal', 'create_time', 'update_time', 'type', 'status'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'match_id' => 'Match ID',
            'user_id' => 'User ID',
            'score' => 'Score',
            'liangan' => 'Liangan',
            'foul' => 'Foul',
            'goal' => 'Goal',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'type' => 'Type',
            'status' => 'Status',
        ];
    }
    public function add($result, $data)
    {
        $tr = Yii::$app->db->beginTransaction();
        foreach ($result as $userID) {
            if($userID['access_token']){
                $data['access_token'] = $userID["access_token"];
            }
            $data['user_id'] = $userID["user_id"];
            $add=$this->addBatch($data);
            try {
                if($add==true){
                    $tr->commit();
                }
            } catch (Exception $e) {
                //回滚
                $tr->rollBack();
                return false;
            }
        }
    }
    public function insertMatchData($params,$matchID)
    {
        $tr = Yii::$app->db->beginTransaction();
        foreach ($params as $value){
            $where = array(
                'user_id' => $value['user_id'],
                'match_id' => $matchID,
                'type'=>0
            );
            $data=array(
                'score'=>$value['score'],
                'liangan'=>$value['liangan'],
                'foul'=>$value['foul'],
                'goal'=>$value['goal'],
                'statistics'=>$value['statistics'],
                'type'=>1,
                'status'=>$value['status'],
                'update_time'=>time(),
                'duration'=>$value['duration'],
                'flee'=>$value['flee']
            );
            try {
                if($this->updateRecord($data,$where)==true){
                    $tr->commit();
                }
            } catch (Exception $e) {
                //回滚
                $tr->rollBack();
            }
        }
        return true;
    }

}
