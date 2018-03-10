<?php
namespace frontend\models;

use Yii;
use yii\db\Exception;

/**
 * This is the model class for table "user".
 *
 * @property string $id 自增id
 * @property string $mobile 手机号
 * @property string $credential 密码
 * @property string $from 注册来源, wechat, xxx
 * @property int $type 用户类型，1:普通用户，2:客服 3:员工
 * @property int $status 用户状态，1:正常，0:注销
 * @property string $provide_type 第三方登录提供方代号(0:应用注册，1:微信,2:qq)
 * @property string $provide_id 第三方open_id
 * @property int $last_time 上次客户端登陆时间
 * @property int $last_wechat_time 上次微信访问时间
 * @property string $identify 用户身份标识，目前是微信id，即unionid
 * @property string $access_token access_token，访问微信的票据信息
 * @property int $identify_expTime 票据过期时间
 * @property string $nickname 用户昵称
 * @property int $age 用户年龄
 * @property string $gender 用户性别(1:男,2:女)
 * @property string $portrait 用户头像
 * @property int $is_follow 是否关注公众号，1:已关注，0:未关注
 * @property string $follow_time 用户关注公众号的时间
 * @property string $trade 用户所在行业
 * @property string $location 用户所在城市
 * @property int $create_time 创建时间
 * @property int $update_time 更新时间
 * @property int $gold 用户金币数
 */
class User extends Common
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'status', 'last_time', 'last_wechat_time', 'identify_expTime', 'age', 'is_follow', 'follow_time', 'create_time', 'update_time', 'gold', 'gender'], 'integer'],
            [['provide_type'], 'string'],
            [['mobile'], 'string', 'max' => 12],
            [['credential', 'trade', 'location'], 'string', 'max' => 100],
            [['from'], 'string', 'max' => 24],
            [['provide_id'], 'string', 'max' => 255],
            [['identify', 'access_token'], 'string', 'max' => 200],
            [['nickname'], 'string', 'max' => 120],
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
            'mobile' => 'Mobile',
            'credential' => 'Credential',
            'from' => 'From',
            'type' => 'Type',
            'status' => 'Status',
            'provide_type' => 'Provide Type',
            'provide_id' => 'Provide ID',
            'last_time' => 'Last Time',
            'last_wechat_time' => 'Last Wechat Time',
            'identify' => 'Identify',
            'access_token' => 'Access Token',
            'identify_expTime' => 'Identify Exp Time',
            'nickname' => 'Nickname',
            'age' => 'Age',
            'gender' => 'Gender',
            'portrait' => 'Portrait',
            'is_follow' => 'Is Follow',
            'follow_time' => 'Follow Time',
            'trade' => 'Trade',
            'location' => 'Location',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'gold' => 'Gold',
        ];
    }
    public function getUserInfoWithUnionID($open_id)
    {
        if (empty($open_id)) {
            return NULL;
        }
        $where = array('provide_id' => $open_id, 'status' => 1);
        $field=array('nickname','portrait','id','gold');
        $record = User::find()->asArray()->where($where)->select($field)->one();
        if (empty($record)) {
            return NULL;
        }
        $now = time();
        $this->updateRecord(array('last_time' => $now, 'update_time' => $now),$where);
        return $record;
    }
    public function getUserOpenId($code){
        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".Yii::$app->params["appid"]."&secret=".Yii::$app->params["appsecret"]."&code=$code&grant_type=authorization_code";
        $res = file_get_contents($url); //获取文件内容或获取网络请求的内容
        $result = json_decode($res,true); //接受一个 JSON 格式的字符串并且把它转换为 PHP 变量
        return $result;
    }
    public function addUser($userList)
    {

        $data = array(
            'nickname'=>$userList['nickname'],
            'gender'=>$userList['sex'],
            'portrait'=>str_replace("/132", "/0", $userList['headimgurl']),
            'location'=>$userList['country'].$userList['province'].$userList['city'],
            'identify'=>$userList['unionid'],
            'provide_id' => $userList['openid'],
            'status' => 1,
            'is_follow' => 0,
            'gold' => 1000000,
            'last_time' => time(),
            'create_time' => time(),
            'update_time'=>time()
        );
        $id = $this->addRecord($data);
        return array('nickname' => $userList['nickname'],'portrait'=>str_replace("/132", "/0", $userList['headimgurl']),'gold'=>1000000,'id'=>$id);
    }
    public function getUserList($result){
        $accesstoken = $result['access_token'];
        $open_id=$result['openid'];
        $userInfo=file_get_contents("https://api.weixin.qq.com/sns/userinfo?access_token=$accesstoken&openid=$open_id&lang=zh_CN");
        return json_decode($userInfo, true);
    }
    public function UpadateUserGold($match_id,$match_list){
        $BilliardsMatch=new BilliardsMatch();
        $BilliardsMatchWhere=array(
            'id'=>$match_id
        );
        $BilliardsMatchColumns=array('bet_id');
        $bet_id=$BilliardsMatch->getRecord($BilliardsMatchWhere,$BilliardsMatchColumns)["bet_id"];
        $bet=new Bet();
        $betWhere=array(
            'id'=>$bet_id
        );
        $betColumns=array('number');
        $bet_number=$bet->getRecord($betWhere,$betColumns)['number'];
        if(count($match_list)==2){
            $win_number=$bet_number*0.9;
        }else if(count($match_list)==3){
            $win_number=$bet_number*0.8;
        };
        $tr = Yii::$app->db->beginTransaction();;
        foreach ($match_list as $value){
            $UserColumns=array('gold');
            $Userwhere = array(
                'id' => $value['user_id'],
            );
            //用户金币
            $gold=$this->getRecord($Userwhere,$UserColumns)['gold'];
            if($value['status']==1){
                $data=array(
                    'gold'=>$gold+$win_number
                );
            }else if($value['status']==0){
                $data=array(
                    'gold'=>$gold-$bet_number,
                    'update_time'=>time()
                );
            }
            try {
                if($this->updateRecord($data,$Userwhere)==true){
                    $tr->commit();
                }
            } catch (Exception $e) {
                //回滚
                $tr->rollBack();
            }
        }
        return true;

    }
    public function getUserInfo($user_id,$mode_id=""){
        $UserWhere=array(
            'id'=>$user_id
        );
        $columns=array(
            'id as user_id','gold','nickname','portrait'
        );
        $userInfo=$this->getRecord($UserWhere,$columns);
        if(empty($userInfo)){
            return false;
        }
        $usercase=$this->getUserCase($user_id,$mode_id);
        if($mode_id){
            $data=$usercase;
        }else{
            $data=array_merge($userInfo, $usercase);
        }
        return $data;
    }
    public function getUserCase($user_id,$mode_id=null){
        $UseCountWhere=array(
            'user_id'=>$user_id,
        );
        if(!empty($mode_id)){
            $mode_id=array('mode_id'=>$mode_id);
            $UseCountWhere=array_merge($UseCountWhere,$mode_id);
        }
        $UseCountColumns=array(
            'count(id) as usecount',
        );
        $Analysis=new BilliardsMatchAnalysis();
        //使用次数
        $UseCount=$Analysis->getRecord($UseCountWhere,$UseCountColumns);
//        var_dump($UseCount);die;
        $WinCountWhere=array(
            'user_id'=>$user_id,
            'status'=>1
        );
        if(!empty($mode_id)){
            $mode_id=array('mode_id'=>$mode_id);
            $WinCountWhere=array_merge($WinCountWhere,$mode_id);
        }
        $WinCountColumns=array(
            'count(id) as wincount',
        );
        //胜利次数
        $WinCount=$Analysis->getRecord($WinCountWhere,$WinCountColumns);
        $MissedCountWhere=array(
            'user_id'=>$user_id,
            'status'=>0
        );
        if(!empty($mode_id)){
            $mode_id=array('mode_id'=>$mode_id);
            $MissedCountWhere=array_merge($MissedCountWhere,$mode_id);
        }
        $MissedCountColumns=array(
            'count(id) as missedCount',
        );
        //失败次数
        $MissedCount=$Analysis->getRecord($MissedCountWhere,$MissedCountColumns);
        //胜率
        $odds['odds']=!empty($UseCount['usecount'])?floor($WinCount['wincount']/$UseCount['usecount']*100):0;
        $FleeCountWhere=array(
            'user_id'=>$user_id,
            'flee'=>1
        );
        if(!empty($mode_id)){
            $mode_id=array('mode_id'=>$mode_id);
            $FleeCountWhere=array_merge($FleeCountWhere,$mode_id);
        }
        $FleeCountColumns=array(
            'count(id) as fleecount',
        );
        //逃跑次数
        $FleeCount=$Analysis->getRecord($FleeCountWhere,$FleeCountColumns);
        $UseCount=$UseCount['usecount']?$UseCount['usecount']:0;
        $WinCount=$WinCount['wincount']?$WinCount['wincount']:0;
        $MissedCount=$MissedCount['missedcount']?$MissedCount['missedcount']:0;
        $odds=$odds['odds']?$odds['odds']:0;
        $FleeCount=!empty($FleeCount['fleecount'])?$FleeCount['fleecount']:0;
        $data=array('usecount'=>$UseCount,'wincount'=>$WinCount,'missedcount'=>$MissedCount,'odds'=>$odds,'fleecount'=>$FleeCount);
        return $data;
    }
}
