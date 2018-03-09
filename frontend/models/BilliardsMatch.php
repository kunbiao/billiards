<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "billiards_match".
 *
 * @property string $id
 * @property int $type 比赛类型，1:线上，2:线下
 * @property int $mode 比赛模式：1：黑八1V1  2：三人欢乐赛  3：15球积分赛   4：黑八积分赛
 * @property string $begin_time 比赛开始时间
 * @property string $end_time 比赛结束时间
 * @property int $status 状态 0:无效，1:比赛进行中，2:比赛结束
 * @property string $access_token 机器id
 * @property int $create_time 比赛创建时间
 * @property int $update_time 比赛修改时间
 */
class BilliardsMatch extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'billiards_match';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mode_id', 'begin_time', 'end_time', 'status', 'create_time', 'update_time','bet_id'], 'integer'],
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
            'mode' => 'Mode',
            'begin_time' => 'Begin Time',
            'end_time' => 'End Time',
            'status' => 'Status',
            'access_token' => 'Access Token',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
    public function closeClient($matchId,$data){
        \frontend\models\Gateway::$registerAddress = '192.168.1.9:1238';
        $Matchwhere=array(
            'id'=>$matchId
        );
        $Matchcolumns='mode_id';
        $mode_id=$this->getRecord($Matchwhere,$Matchcolumns);
        $BilliardsType=new BilliardsTypeMode();
        $Modewhere=array(
            'id'=>$mode_id['mode_id']
        );
        $Modecolumns='type_id';
        $type=$BilliardsType->getRecord($Modewhere,$Modecolumns);
        $matchList=$data['match_list'];
        if($type['type_id']==1){
            if($matchList[0]['user_id']==$matchList[1]['user_id'] || $matchList[0]['client_id']==$matchList[1]['client_id']){
                return false;
            }
            foreach ($matchList as $client_id){
            if(empty($client_id['client_id'])){
                return false;
            }
            if(strlen($client_id['client_id'])!=20){
                return false;
            }
            if(!\frontend\models\Gateway::isOnline($client_id['client_id'])){
                return false;
            }
                $MatchAnalysis=new BilliardsMatchAnalysis();
                if(empty($MatchAnalysis->getRecord(array('match_id'=>$matchId,'user_id'=>$client_id['user_id'])))){
                    return false;
                }
            }
            foreach($matchList as $key){
                //解除绑定
                \frontend\models\Gateway::unbindUid($key['client_id'],$key['user_id']);
            }
        }
        return true;
    }
    public function isMatchStatsSaved($matchID,$_post)
    {
        if(count($_post['match_list'])!=2){
            return false;
        }
        $where = array(
            'id' => $matchID,
            'status' => 2
        );
        return !empty(Court::find()->asArray()->where($where)->one());
    }
    public function UpadateMatchStats($matchID){
        $where = array(
            'id' => $matchID,
            'status' => 1
        );
        $data=array(
            'status'=>2,
            'update_time'=>time(),
            'end_time'=>time()
        );
        return $this->updateRecord($data,$where);
    }
//    public function getRedisKey($user_id){
//        $where=array(
//            ''
//        );
//    }
}
