<?php
/**
 * @name MatchController
 * @author
 * @desc
 */
namespace frontend\controllers;
use Codeception\Module\Redis;
use frontend\models\Bet;
use frontend\models\BilliardsMatch;
use frontend\models\BilliardsMatchAnalysis;
use frontend\models\BilliardsTypeMode;
use frontend\models\Court;
use frontend\models\RoomTime;
use frontend\models\User;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Yii;
class MatchController extends CommonController
{

    public function init()
    {
        parent::init();
        $this->_bizType     = "match";
    }
    public function verify($access_token){
        $court=new Court();
        $courtData = $court->getRecord(array('access_token' => $access_token));

        if (empty($courtData)) {
            $this->_errCode = 1002;
            $this->_errMsg = '非法的token';
            $this->outputMsg();
        }
    }
    public function actionGetmatchid()
    {
        $this->_func = __FUNCTION__;
        $this->chkDebug();
        $access_token = $this->_postData['access_token'];
        $this->verify($access_token);
        $data = $this->addMatchRecord($this->_postData);
        if (empty($data)) {
            $this->_errCode = 1003;
            $this->_errMsg = '添加比赛ID失败';
            $this->outputMsg();
        }
        $this->_reinfo = $data;
        $this->outputMsg();

    }
    public function actionSubmitstatistics()
    {
        $this->_func = __FUNCTION__;
        $this->chkDebug();
        $matchID = $this->_postData['match_id'];
        $BilliardsMatch=new BilliardsMatch();
        if (empty($matchID)) {
            $this->_errCode = 1004;
            $this->_errMsg = '请输入比赛id';
            $this->outputMsg();
        }
        if(!$BilliardsMatch->closeClient($matchID,$this->_postData)){
            $this->_errCode = 1112;
            $this->_errMsg = '请输入正确的客户端Id或者用户id';
            $this->outputMsg();
        }
        if ($BilliardsMatch->isMatchStatsSaved($matchID,$this->_postData)) {
            $this->_errCode = 1005;
            $this->_errMsg = '重复的比赛技术统计';
            $this->outputMsg();
        }
        if ($BilliardsMatch->UpadateMatchStats($matchID)==false) {
            $this->_errCode = 1006;
            $this->_errMsg = '修改比赛状态失败请输入正确的比赛id';
            $this->outputMsg();
        }

        $user=new User();
        if ($user->UpadateUserGold($matchID,$this->_postData['match_list'])==false) {
            $this->_errCode = 1006;
            $this->_errMsg = '修改用户金币失败请重试';
            $this->outputMsg();
        }
        $BilliardsMatch=new BilliardsMatchAnalysis();
        if (!$BilliardsMatch->insertMatchData($this->_postData['match_list'],$matchID)) {
            $this->_errCode = 1007;
            $this->_errMsg = '保存比赛技术统计失败,请重试';
            $this->outputMsg();
        }

        $this->outputMsg();
    }
    private function addMatchRecord($result)
    {
        if(count($result['match_list'])!=2){
            return false;
        }
        $this->confine($result,2);
        $user=new User();
        foreach ($result['match_list'] as $userid){
            if(empty($userid['user_id'])){
                $this->_errCode = 21;
                $this->_errMsg = '请传user_id参数';
                $this->outputMsg();
            }
            if(!$user->getRecord(array('id'=>$userid['user_id']))){
                $this->_errCode = 22;
                $this->_errMsg = '请输出正确user_id';
                $this->outputMsg();
            }
        }
        $matchMode=$result['match_mode'];
        $beginTime = time();
        $access_token=empty($result['access_token']) ? 1 : $result['access_token'];
        $bet_id= $result['bet_id'];
        $matchRecord = array(
            'mode_id' => $matchMode,
            'begin_time' => $beginTime,
            'status' => 1,
            'bet_id' => $bet_id,
            'create_time' => time(),
            'update_time' => time()
        );
        $BilliardsMatch=new BilliardsMatch();
        $matchID = $BilliardsMatch->addRecord($matchRecord);
        $this->addUserMatchRecord($matchID,$access_token,$matchMode,$result['match_list']);
        return array('match_id'=>$matchID);
    }
    private function addPlayerMatchRecord($result)
    {
        $matchMode = empty($result[0]['match_mode']) ? 1 : $result[0]['match_mode'];
        $beginTime = time();
        $bet_id=empty($result[0]['bet_id']) ? 1 : $result[0]['bet_id'];
        $matchRecord = array(
            'mode_id' => $matchMode,
            'begin_time' => $beginTime,
            'status' => 1,
            'bet_id' => $bet_id,
            'create_time' => time(),
            'update_time' => time()
        );
        $BilliardsMatch=new BilliardsMatch();
        $matchID = $BilliardsMatch->addRecord($matchRecord);
        $this->addPlayerUserMatchRecord($matchID,$matchMode,$result);
        return $matchID;
    }
    private function addUserMatchRecord($matchID,$access_token,$matchMode,$result)
    {
        $userMatch = array(
            'match_id' => $matchID,
            'status' => 1,
            'access_token'=>$access_token,
            'create_time' => time(),
            'update_time' => time(),
            'mode_id' => $matchMode,
        );
        if (empty($result))
            return;
        $UserMath=new BilliardsMatchAnalysis();
        $UserMath->add($result, $userMatch);
    }
    private function addPlayerUserMatchRecord($matchID,$matchMode,$result)
    {
        $userMatch = array(
            'match_id' => $matchID,
            'status' => 1,
            'create_time' => time(),
            'update_time' => time(),
            'mode_id' => $matchMode,
        );
        if (empty($result))
            return;
        $UserMath=new BilliardsMatchAnalysis();
        $UserMath->add($result, $userMatch);
    }
    //线上比赛添加
    public function actionAddplayer(){
        \frontend\models\Gateway::$registerAddress = '192.168.1.9:1238';
        $this->_func = __FUNCTION__;
        $this->chkDebug();
        $redis = Yii::$app->redis;
        $use_time=$this->confine($this->_postData,1);
        $this->_postData=array_merge($this->_postData,$use_time);
        $user=new User();
        if(empty($this->_postData['user_id'])){
            $this->_errCode = 21;
            $this->_errMsg = '请传user_id参数';
            $this->outputMsg();
        }
        if(!$user->getRecord(array('id'=>$this->_postData['user_id']))){
            $this->_errCode = 22;
            $this->_errMsg = '请输出正确user_id';
            $this->outputMsg();
        }
        $access_token = $this->_postData['access_token'];
        $client_id=$this->_postData['client_id'];
        if(strlen($client_id)!=20){
            $this->_errCode = 88;
            $this->_errMsg = '请输入正确的client_id';
            $this->outputMsg();
        }
        if(!\frontend\models\Gateway::isOnline($client_id)){
            $this->_errCode = 89;
            $this->_errMsg = '该client_id已经下线或不存在';
            $this->outputMsg();
        }
        if(!empty($redis->EXISTS($this->_postData['user_id']))){
            $this->_errCode = 99;
            $this->_errMsg = '该user_id匹配中，请勿重复匹配';
            $this->outputMsg();
        }
        \frontend\models\Gateway::bindUid($client_id, $this->_postData['user_id']);

        $this->verify($access_token);
        //使用队列 添加用户 匹配成功踢出 然后告诉前端
        $userInfo=$this->Matching($this->_postData);
        if($userInfo){
            $matchID = $this->addPlayerMatchRecord($userInfo);
            if (empty($matchID)) {
                $this->_errCode = 1003;
                $this->_errMsg = '添加比赛ID失败';
                $this->outputMsg();
            }
            $userInfoA["user_list"]=$user->getUserInfo($userInfo[0]['user_id']);
            $userInfo['type']="login";
            $userInfo['match_id']=$matchID;
            $type=array('type'=>'login');
            $matchID=array('match_id'=>$matchID);
            $client_id=array('client_id'=>$userInfo[0]['client_id']);
            $use_time=array('use_time'=>$userInfo[0]['use_time']);
            $userInfoA=array_merge($userInfoA['user_list'],$client_id,$type,$matchID,$use_time);
            $userInfoB["user_list"]=$user->getUserInfo($userInfo[1]['user_id']);
            $client_id=array('client_id'=>$userInfo[1]['client_id']);
            $userInfoB=array_merge($userInfoB['user_list'],$client_id,$type,$matchID,$use_time);
            \frontend\models\Gateway::sendToUid($userInfoB['user_id'], json_encode($userInfoA));
            \frontend\models\Gateway::sendToUid($userInfoA['user_id'], json_encode($userInfoB));
        }
        $this->outputMsg();
    }
    //匹配玩家
    public function Matching($data){
        $match_mode=$data['match_mode'];
        $bet_id=$data['bet_id'];
        $key=$match_mode.$bet_id;
        $redis = Yii::$app->redis;
        // 判断 key 为 username 的是否有值，有则打印，没有则赋值
        $json_value=json_encode($data);
        $redis->lpush('myslist'.$key,$json_value);
//        $redis->lpush($data['client_id'],$data['user_id']);
        $redis->lpush($data['user_id'],'myslist'.$key);
        //获取集合里的用户数
        $UserCount=$redis->llen('myslist'.$key);
        if($UserCount>=2) {
            // 随机取2个用户进行比赛
            $fastInfoJson = $redis->Lpop('myslist' . $key);
            $lastInfoJson = $redis->Rpop('myslist' . $key);
            $fastInfo = json_decode($fastInfoJson, true);
            $lastInfo = json_decode($lastInfoJson, true);
            $redis = Yii::$app->redis;
            $redis->del($fastInfo['user_id']);
            $redis->del($lastInfo['user_id']);
            $info = array($fastInfo, $lastInfo);
            return $info;
        }else{
            return false;
        }
    }
    //用户取消匹配
    public function actionCancel(){
        $this->chkDebug();
        $user=new User();
        $user_id=$this->_postData['user_id'];
        if(empty($user_id)){
            $this->_errCode = 21;
            $this->_errMsg = '请传user_id参数';
            $this->outputMsg();
        }
        if(!$user->getRecord(array('id'=>$user_id))){
            $this->_errCode = 22;
            $this->_errMsg = '请输出正确user_id';
            $this->outputMsg();
        }
        $redis = Yii::$app->redis;
        $mate=$redis->LINDEX($user_id,0);
        $mateKeyLength=$redis->LLEN($mate);
        \frontend\models\Gateway::$registerAddress = '192.168.1.9:1238';
        if(!empty($redis->keys($user_id)) || $mateKeyLength==1){
            //获取当前用户连接的客户端id
            $client_id=\frontend\models\Gateway::getClientIdByUid($user_id);
            //解除绑定
            \frontend\models\Gateway::unbindUid($client_id[0],$user_id);
            $redis->Lpop($mate);
            $redis->del($user_id);
        }else{
            $this->_errCode = 1;
            $this->_errMsg = '正在比赛中或者已经取消匹配';
        }
        $this->outputMsg();
    }
    public function confine($result,$type_id){
        if(empty($result['match_mode'])){
            $this->_errCode = 18;
            $this->_errMsg = '请传match_mode参数';
            $this->outputMsg();
        }
        $TypeMode=new BilliardsTypeMode();
        if(!$mode_id=$TypeMode->getRecord(array('mode_id'=>$result['match_mode'],'type_id'=>$type_id),'mode_id')){
            $this->_errCode = 19;
            $this->_errMsg = '请输入当前模式正确match_mode';
            $this->outputMsg();
        }
        if(empty($result['bet_id'])){
            $this->_errCode = 20;
            $this->_errMsg = '请传bet_id参数';
            $this->outputMsg();
        }
        $bet=new Bet();
        if(!$bet->getRecord(array('id'=>$result['bet_id']))){
            $this->_errCode = 31;
            $this->_errMsg = '请输入正确的bet_id';
            $this->outputMsg();
        }
        $TypeMode=new RoomTime();
        if($type_id==1){
            $use_time=$TypeMode->getRecord(array('bet_id'=>$result['bet_id'],'mode_id'=>$mode_id['mode_id']),'use_time');
            return $use_time;
        }
    }
}
