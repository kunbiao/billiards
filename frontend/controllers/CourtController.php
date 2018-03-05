<?php
/**
 * @name CourtController
 * @author winky
 * @desc
 */
namespace frontend\controllers;

use frontend\models\Court;

class CourtController extends \frontend\controllers\CommonController
{
    public function init()
    {

        parent::init();
        //当前业务
        $this->_bizType     = "court";
    }

    public function actionGetcourtinfo()
    {
        $this->_func = __FUNCTION__;
        //错误信息
        $this->chkGroundData();
        $groundID = $this->_postData['ground_id'];
        $Court=new Court();
        $courtInfo = $Court->getCourtInfo($groundID);
        if(empty($courtInfo)) {
            $this->_errCode = 12;
            $this->_errMsg = '非法的场地ID';
            $this->outputMsg();
        }
        var_dump($courtInfo);die;
//        trace(json_encode($courtInfo), 'getCourtInfoAction', 'DEBUG', true);
        $this->_reinfo = $courtInfo;
        $this->outputMsg();
    }

    private function chkGroundData()
    {
        //检测是否为空
        $this->chkPostData();
        $this->chkRequestParams();
        $token = $this->_postData['access_token'];
        $groundID = $this->_postData['ground_id'];
        $Court=new Court();
        if (!$Court->isValidToken($token, $this->_clientVersion)) {
            $this->_errCode = 11;
            $this->_errMsg = "非法的令牌";
            $this->outputMsg();
        }
        return $Court->createGroundToken($token, $groundID, $this->_clientVersion);
    }
}

