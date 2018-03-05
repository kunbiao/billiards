<?php
namespace frontend\controllers;

use frontend\models\Bet;
use frontend\models\BilliardsTypeMode;
use frontend\models\User;

class UserController extends CommonController
{
    public function init()
    {
        parent::init();
        $this->_bizType     = "user";
    }

    public function actionLogin()
    {
        $this->_func = __FUNCTION__;
        $this->chkDebug();
        $code = $this->_postData['code'];
        if (empty($code)) {
            $this->_errCode = 101;
            $this->_errMsg = 'code为空';
            $this->outputMsg();
        }
        $user=new \frontend\models\User();
        $result=$user->getUserOpenId($code);
        if($result['errcode']==40163 || $result['errcode']==40029){
            $this->_errCode = 102;
            $this->_errMsg = '请输入正确的code';
            $this->outputMsg();
        }
        $userInfo = $user->getUserInfoWithUnionID($result['openid']);
        if (empty($userInfo)) {
            $userList=$user->getUserList($result);
            $userInfo = $user->addUser($userList);
        }

        $this->_reinfo = $userInfo;
        $this->outputMsg();
    }

    public function actionBet()
    {
        $bet=new Bet();
        $betList['bet_list']=$bet->getAllRecord();
        $this->_reinfo = $betList;
        $this->outputMsg();
    }
    //获取用户个人信息
    public function actionUserinfo(){
        $this->_func = __FUNCTION__;
        $this->chkDebug();
        $user_id = $this->_postData['user_id'];
        $mode_id = !empty($this->_postData['mode_id'])?$this->_postData['mode_id']:null;
        if(empty($mode_id) && $mode_id!=0){
            $this->_errCode = 100;
            $this->_errMsg = '模式id为空';
            $this->outputMsg();
        }
        $TypeMode=new BilliardsTypeMode();
        if(!$TypeMode->getRecord(array('id'=>$mode_id)) && $mode_id!=0){
            $this->_errCode = 19;
            $this->_errMsg = '请输出正确match_mode';
            $this->outputMsg();
        }
        $user=new User();
        $userInfo=$user->getUserInfo($user_id,$mode_id);
        if(empty($userInfo)){
            $this->_errCode = 105;
            $this->_errMsg = '请输入正确的user_id';
            $this->outputMsg();
        }
        $this->_reinfo = $userInfo;
        $this->outputMsg();
    }
    //获取用户每个模式信息
    public function actionModeinfo(){
        
    }

}