<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "court".
 *
 * @property string $id
 * @property string $site_id 场馆id
 * @property int $type 场地类型，室内、室外、气膜什么的
 * @property string $ground_id 场地id
 * @property string $name 场地名字
 * @property string $access_token 场地终端初始化密钥
 * @property string $token_exptime token过期时间
 * @property string $client_version_code 客户端程序版本号
 * @property string $server_cfg_version_code 服务端配置文件版本号
 * @property string $server_bin_version_code 服务端程序版本号
 * @property string $client_url 客户端程序下载地址
 * @property string $server_cfg_url 服务端配置文件下载地址
 * @property string $server_bin_url 服务端程序下载地址
 * @property int $status 状态, 0:终端未激活，1:终端已成功激活
 * @property string $create_time
 * @property string $activate_time 激活时间
 * @property string $update_time
 */
class Court extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'court';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['site_id', 'type', 'client_version_code', 'server_cfg_version_code', 'server_bin_version_code', 'status'], 'integer'],
            [['token_exptime', 'create_time', 'activate_time', 'update_time'], 'safe'],
            [['ground_id', 'name', 'access_token'], 'string', 'max' => 64],
            [['client_url', 'server_cfg_url', 'server_bin_url'], 'string', 'max' => 250],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'site_id' => 'Site ID',
            'type' => 'Type',
            'ground_id' => 'Ground ID',
            'name' => 'Name',
            'access_token' => 'Access Token',
            'token_exptime' => 'Token Exptime',
            'client_version_code' => 'Client Version Code',
            'server_cfg_version_code' => 'Server Cfg Version Code',
            'server_bin_version_code' => 'Server Bin Version Code',
            'client_url' => 'Client Url',
            'server_cfg_url' => 'Server Cfg Url',
            'server_bin_url' => 'Server Bin Url',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'activate_time' => 'Activate Time',
            'update_time' => 'Update Time',
        ];
    }
    public function isValidToken($token, $clientVersion)
    {
        $where = array(
            'access_token' => $token,
            'client_version_code' => $clientVersion
            // 'status' => 0
        );
        return !empty(Court::find()->asArray()->where($where)->one());
    }

    public function createGroundToken($token, $groundID, $version)
    {
        $time = time();
        $sessionID = md5($token.md5("@~cookie#_{$groundID}_{$time}"));

        // update client session.
        $where = array(
            'access_token' => $token,
            'client_version_code' => $version,
            // 'status' => 0
        );
        $data = array(
            'ground_id' => $groundID,
            'access_token' => $sessionID,
            'activate_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'status' => 1
        );
        $res = $this->updateRecord($data, $where);
        if (empty($res)) {
            return "";
        }

        return $sessionID;
    }

    public function chkGroundToken($token, $groundID)
    {
        $sessionData = $this->getGroundToken($token,$groundID);
        if (empty($sessionData)) {
            return false;
        }

        // $expire = strtotime($sessionData['token_exptime']);
        // if (time() > $expire) {
        //     // 票据过期
        //     return false;
        // }
        return 0 == strcasecmp($token, $sessionData['access_token']);
    }

    public function getCourtInfo($groundID)
    {
        $result = array();
        $where = array('ground_id' => $groundID, 'status' => 1);
        Court::find()->where($where)->one();
//        $courtRecord = $this->where($where)->find();
        $courtRecord = Court::find()->asArray()->where($where)->one();
        if (empty($courtRecord)) {
            return $result;
        }
        $siteID = $courtRecord['site_id'];
        $site=new Site();
        $siteRecord = $site->getSiteInfo($siteID);
        $siteRecord['coordinate'] = array('lat' => $siteRecord['latitude'], 'lng' => $siteRecord['longitude']);
        $token = $courtRecord['access_token'];
        unset($courtRecord['site_id']);
        unset($courtRecord['id']);
        unset($courtRecord['type']);
        unset($courtRecord['ground_id']);
        unset($courtRecord['access_token']);
        unset($courtRecord['status']);
        unset($courtRecord['create_time']);
        unset($courtRecord['update_time']);
        unset($courtRecord['client_version_code']);
        unset($courtRecord['server_cfg_version_code']);
        unset($courtRecord['server_bin_version_code']);
        unset($courtRecord['client_url']);
        unset($courtRecord['server_cfg_url']);
        unset($courtRecord['server_bin_url']);
        unset($siteRecord['id']);
        unset($siteRecord['type']);
        unset($siteRecord['longitude']);
        unset($siteRecord['latitude']);
        unset($siteRecord['ground_num']);
        unset($siteRecord['status']);
        unset($siteRecord['create_time']);
        unset($siteRecord['update_time']);

        $result['site'] = $siteRecord;
        $result['court'] = $courtRecord;
        $result['access_token'] = $token;
        return $result;
    }

    public function getUpdateInfo($groundID, $sourceType)
    {
        $result = array();
        $where = array('ground_id' => $groundID, 'status' => 1);
        $updateInfo = Court::find()->where($where)->asArray()->one();
        if (empty($updateInfo)) {
            return $result;
        }

        if ($sourceType == 2) {
            unset($updateInfo['client_version_code']);
            unset($updateInfo['client_url']);
        } else {
            unset($updateInfo['server_cfg_version_code']);
            unset($updateInfo['server_bin_version_code']);
            unset($updateInfo['server_cfg_url']);
            unset($updateInfo['server_bin_url']);
        }

        unset($updateInfo['site_id']);
        unset($updateInfo['id']);
        unset($updateInfo['type']);
        unset($updateInfo['ground_id']);
        unset($updateInfo['name']);
        unset($updateInfo['access_token']);
        unset($updateInfo['lf']);
        unset($updateInfo['ld']);
        unset($updateInfo['lw']);
        unset($updateInfo['dd']);
        unset($updateInfo['ds']);
        unset($updateInfo['dw']);

        return $updateInfo;
    }

    public function updateGroundToken($token, $groundID, $clientVersion)
    {
        $time = time();
        $sessionID = md5($token.md5("@~cookie#_{$groundID}_{$time}"));

        // update client session.
        $where = array(
            'ground_id' => $groundID,
            'client_version' => $clientVersion,
            'status' => 1
        );
        $data = array(
            'access_token' => $sessionID,
            'update_time' => date('Y-m-d H:i:s')
        );
         $res = $this->updateRecord($data, $where);
         if (empty($res)) {
             return false;
         }

        return $sessionID;
    }

    private function getGroundToken($token,$groundID)
    {
        $where = array(
            'ground_id' => $groundID,
            'status' => 1,
            'access_token'=>$token
        );
        return Court::find()->asArray()->where($where)->one();
    }
    public function getSiteId($access_token){
        $site_idSql = "select site_id from court where access_token=:access_token";
        $site_id=Yii::$app->getDb()->createCommand($site_idSql,[":access_token"=>$access_token])->queryAll();
        return $site_id;
    }
}
