<?php
/*
 * 小程序公共
 */
namespace Wxapp\Controller;
use Think\Controller\RestController;
class CommonController extends RestController{
	public function _initialize() {
		$wxapp_settings=sp_get_option('wxapp_settings');
		$this->appid=$wxapp_settings['appid'];
		$this->appsecret=$wxapp_settings['appsecret'];
	}
	//发送模板消息
	public function sendmessage(){
		$data=$_POST=json_decode(file_get_contents('php://input'), TRUE);
		$access_token=$this->getAccessToken();
		$request_url='https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;
		$request_data=array(
			'touser'=>$data['touser'],//接收者（用户）的 openid
			'template_id'=>$data['template_id'],//所需下发的模板消息的id
			'page'=>$data['page'],//点击模板卡片后的跳转页面
			'form_id'=>$data['form_id'],//表单提交场景下，为 submit 事件带上的 formId；支付场景下，为本次支付的 prepay_id
			'data'=>$data['data'],//"keyword1": {"value": "339208499", "color": "#173177"}
			'emphasis_keyword'=>$data['emphasis_keyword']//模板需要放大的关键词，不填则默认无放大
		);
		$return=json_decode(https_request($request_url,$request_data,'json'),true);
		$this->response($return,'json');
	}
	public function getSession(){
		$code=$_POST['code'];
		if(!$code){
			$data['errmsg']='code为空';
			$data['error']=1;
			$this->response($data,'json');
			exit;
		}
		if(!$_POST['userInfo']){
			$data['errmsg']='userInfo为空';
			$data['error']=1;
			$this->response($data,'json');
			exit;
		}
		$userInfo=json_decode($_POST['userInfo'],true);

		$return=$this->updateSession($code,$userInfo);
		$this->response($return,'json');
	}
	//获取AccessToken
	private function getAccessToken(){
		$access_token_Model=M('access_token','wei_');
		$access_token_Data=$access_token_Model->find();
		if($access_token_Data['access_token']&&time()<$access_token_Data['access_token_update_time']+$access_token_Data['access_token_expires_in']){
			$return=array('access_token'=>$access_token_Data['access_token'],'expires_in'=>$access_token_Data['access_token_expires_in']);
		}else{
			$url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->appsecret;
			$return=json_decode(https_request($url),true);
			if($return['access_token']){
				$save=array(
					'access_token'=>$return['access_token'],
					'access_token_expires_in'=>$return['expires_in'],
					'access_token_update_time'=>time()
				);
				$access_token_Model->where(array('appid'=>$this->appid))->data($save)->save();
			}
		}
		return $return['access_token'];
	}
	//刷新session
	private function updateSession($code,$userInfo){
		$return=$this->getOAuth($code);
		if(!$return['session_key']){
			$data['errmsg']='getOAuth函数报错:'.json_encode($return);
			$data['error']=1;
			return $data;
			exit;
		}
//		$unionid_Data=json_decode($this->getUnionID($return['session_key'],$userInfo),true);
//		if(!$unionid_Data['unionId']){
//			$data['errmsg']='getUnionID函数报错:'.json_encode($unionid_Data);
//			$data['error']=1;
//			return $data;
//			exit;
//		}
		$wei_user_Model=M('user','wei_');
		$exist=$wei_user_Model->where(array('openid'=>$return['openid']))->find();
		if($exist){
			$save=array(
				//'unionid'=>$unionid_Data['unionId'],
				'session_key'=>$return['session_key'],
				'nickname'=>$userInfo['userInfo']['nickName'],
				'gender'=>$userInfo['userInfo']['gender'],
				'avatarUrl'=>$userInfo['userInfo']['avatarUrl'],
				'province'=>$userInfo['userInfo']['province'],
				'city'=>$userInfo['userInfo']['city'],
				'expires_in'=>$return['expires_in'],
				'dateline'=>time()
			);
			$is_save=$wei_user_Model->where(array('openid'=>$return['openid']))->data($save)->save();
		}else{
			$add=array(
				'openid'=>$return['openid'],
				//'unionid'=>$unionid_Data['unionId'],
				'session_key'=>$return['session_key'],
				'nickname'=>$userInfo['userInfo']['nickName'],
				'gender'=>$userInfo['userInfo']['gender'],
				'avatarUrl'=>$userInfo['userInfo']['avatarUrl'],
				'province'=>$userInfo['userInfo']['province'],
				'city'=>$userInfo['userInfo']['city'],
				'expires_in'=>$return['expires_in'],
				'dateline'=>time()
			);
			$is_add=$wei_user_Model->data($add)->add();
			if($is_add){
				$users_Model=M('users');
				$ask_add=array(
					'user_login'=>$userInfo['userInfo']['nickName'],
					'user_pass'=>'###5adde8ba6f6b2a8ad995cff43fed6251',
					'user_nicename'=>$userInfo['userInfo']['nickName'],
					'avatar'=>$userInfo['userInfo']['avatarUrl'],
					'last_login_ip'=>get_client_ip(),
					'last_login_time'=>date("Y-m-d H:i:s", time()),
					'create_time'=>date("Y-m-d H:i:s", time()),
					'user_status'=>1,
					'user_type'=>2,
					'wxapp_uid'=>$return['openid']
				);
				$is_ask_add=$users_Model->data($ask_add)->add();
			}
		}
		if($is_save!==FALSE || $is_add){
			$wei_user_Data=$wei_user_Model->where(array('openid'=>$return['openid']))->find();
			$this->setSession($return['session_key']);
			$data['session']=session('session');
			$data['expires']=time()+$return['expires_in'];
			$data['error']=0;
		}else{
			$data['errmsg']="session更新失败！";
			$data['error']=1;
		}
		return $data;
	}
	//设置session
	private function setSession($session){
		if(!empty($session)){ 
			session('session',$session);
		}
	}
	//发送code授权
	private function getOAuth($code){
		$url='https://api.weixin.qq.com/sns/jscode2session';
		$data=array(
			'appid'=>$this->appid,
			'secret'=>$this->appsecret,
			'js_code'=>$code,
			'grant_type'=>'authorization_code'
		);
		$return=json_decode(https_request($url, $data),true);
		return $return;
	}
	//得到unionid 提交sessionKey、userinfo、
	private function getUnionID($sessionKey,$userInfo){
		if(!$userInfo['encryptedData']){
			return 'encryptedData为空';
			exit;
		}
		if(!$userInfo['iv']){
			return 'iv为空';
			exit;
		}
        vendor('wxBizDataCrypt.wxBizDataCrypt');

		$encryptedData=$userInfo['encryptedData'];
		$iv = $userInfo['iv'];
		
		$pc = new \WXBizDataCrypt($this->appid, $sessionKey);
		$errCode = $pc->decryptData($encryptedData, $iv, $data );

		if ($errCode == 0) {
			return $data;
		}else{
			return $errCode;
		}
	}
	//支付回调
	public function payNotify(){
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		$array=$this->FromXml($xml);
		if($array['return_code']=='SUCCESS'){
			//马拉松俱乐部
			if($array['attach']=='MarathonClubJoinfee'){
				$marathonclub_join_Model=M('marathonclub_join','wei_');
				$marathonclub_join_Data=$marathonclub_join_Model->where(array('order_id'=>$array['out_trade_no']))->find();
				if(!$marathonclub_join_Data['is_pay']){
					$is_save=$marathonclub_join_Model->where(array('order_id'=>$array['out_trade_no']))->data(array('is_pay'=>1))->save();
					if($is_save){
						$return['return_code']='SUCCESS';
						$returnXml=$this->arrayToXml($return);
					}else{
						$return['return_code']='FAIL';
						$returnXml=$this->arrayToXml($return);
					}
				}else{
					$return['return_code']='FAIL';
					$returnXml=$this->arrayToXml($return);
				}
			}
			
			echo $returnXml;
		}
	}
    //将xml转为array
	private function FromXml($xml){	
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $this->values;
	}
}
	