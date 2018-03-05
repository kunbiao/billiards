<?php
/**
 * @name CommonController
 * @author winky
 * @desc 公共控制器
 * @version 1.0
 */
namespace frontend\controllers;
use frontend\models\Court;
use Yii;
use yii\base\Controller;

class CommonController extends Controller
{
    protected $_clientVersion;          /*客户端版本号*/
    protected $_debug;                   /*调试参数*/
    protected $_beginTime;              /*开始时间*/
    protected $_endTime;                /*结束时间*/
    protected $_message;                /*定义消息*/
    protected $_func = '';              /*当前函数名*/
    protected $_bizType = '';           /*当前业务*/
    protected $_log;                    /*日志配置*/
    protected $_errCode    = 0;        /*接口错误代码*/
    protected $_errMsg     = '';       /*接口信息*/
    protected $_reinfo     = array();  /*接口默认返回数据*/
    protected $_clientID;                /*登陆用户ID*/
    protected $_params;                  /*url参数*/
    protected $_postData   = array();  /*POST数据*/

    /**
     * @构造函数
     */
    public function init() {

        $this->_beginTime = getMicrotime();

        $this->getURIParams();
        $this->_clientVersion  = isset($this->_params['c_version'])? $this->_params['c_version'] : 1;

        $this->_debug  = isset($this->_params['debug'])? $this->_params['debug'] : 0;
    }

    /**
     * @是否是DEBUG模式--直接获取指定用户数据
     */
    protected function chkDebug()
    {
        $this->chkPostData();
        $this->chkRequestParams();
        //debug不是199的时候
        if ($this->_debug != 199) {
            $token = $this->_postData['access_token'];
            $groundID = $this->_postData['ground_id'];
            $Court=new Court();
            if (!$Court->chkGroundToken($token, $groundID)) {
                $this->_errCode = 3;
                $this->_errMsg = "令牌无效或已过期";
                $this->outputMsg();
            }
        }
    }

    public function outputMsg($data = array(), $output = true)
    {
        $message['err_code'] = $this->_errCode;
        $message["err_msg"] = $this->_errMsg;
        if (! empty($this->_reinfo)) {
            $message["data"] = $this->_reinfo;
        }

        if ($message['err_code'] > 0) {
            if (empty($data) && ! empty($this->_postData)) {
                $data = var_export($this->_postData, true);
            }
//             $this->dataErrorLog($data, $this->clientID);
        }
        // $this->businessLog($data);
        if ($output) {
//            var_dump($message);die;
            echo str_replace("\\/", "/", json_encode($message,JSON_UNESCAPED_UNICODE));
//            echo json_encode($message);
            die();
        }
    }

    /**
     * @获取URI中的参数
     * @param  void
     * @return array
     */
    protected function getURIParams()
    {
        $request = Yii::$app->request;
        $this->_params = $request->get();
    }

    /**
     * @检查提交数据是否为空
     *
     * @param void
     * @return array
     */
    protected function chkPostData()
    {
        if (count($_POST)) {
            $postData = $_POST;
        } elseif (! empty(file_get_contents('php://input'))) {
            $postData = file_get_contents('php://input');
            $postData = str_replace("'", "", $postData);
            $postData = json_decode($postData, true);
        } else {
            $this->_errCode = 2;
            $this->_errMsg = "没有输入参数";
            $this->outputMsg();
        }
        $this->_postData = $postData;
//        trace(json_encode($this->_postData), 'chkPostData', 'DEBUG', true);
    }

    protected function chkRequestParams()
    {

        $token = $this->_postData['access_token'];
        $groundID = $this->_postData['ground_id'];
        if (empty($token) || empty($groundID)) {
            $this->_errCode = 1;
            $this->_errMsg = "非法的输入参数";
            $this->outputMsg();
        }
    }
}

