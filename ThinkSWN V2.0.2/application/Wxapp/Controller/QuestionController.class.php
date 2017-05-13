<?php
/*
 * 提问
 */
namespace Wxapp\Controller;
use Think\Controller\RestController;
class QuestionController extends RestController{
	//提问
	public function add(){
		$_POST=json_decode(file_get_contents('php://input'), TRUE);
		$wei_user_Model=M('user','wei_');
		$users_Model=M('users');
		$posts_Model=M('posts');
		$term_relationships_Model=M('term_relationships');
		
		$wei_user_Data=$wei_user_Model->where(array('session_key'=>$_POST['session']))->find();
		if($wei_user_Data){
			$users_Data=$users_Model->where(array('wxapp_uid'=>$wei_user_Data['openid']))->find();
			if($users_Data){
				if($_POST['question']){
					foreach($_POST['imgUrls'] as $key=>$val){
						$_POST['question_desc'].='<p><img src="'.$val.'" class="img-responsive" alt="'.$_POST['question'].$key.'" data-bd-imgshare-binded="1"><br></p>';
					}
					$question_add=array(
						'post_author'=>$users_Data['id'],
						'post_date'=>date("Y-m-d H:i:s", time()),
						'post_content'=>$_POST['question_desc'],
						'post_title'=>$_POST['question'],
						'post_modified'=>date("Y-m-d H:i:s", time()),
						'smeta'=>'{"thumb":""}',
						'type'=>'question',
						'form_id'=>$_POST['form_id']
					);
					$is_add=$posts_Model->data($question_add)->add();
					if($is_add){
						$term_relationships_add=array(
							'object_id'=>$is_add
						);
						$is_term_add=$term_relationships_Model->data($term_relationships_add)->add();
						if($is_term_add){
							$data['data'] =$is_add;
							$data['status'] =1;
							$this->response($data,'json');
						}else{
							$posts_Model->where(array('id'=>$is_add))->delete();
							$data['error'] ='提交失败！';
							$data['status'] =0;
							$this->response($data,'json');
						}
					}else{
						$data['error'] ='提交失败！';
						$data['status'] =0;
						$this->response($data,'json');
					}
				}else{
					$data['error'] ='问题不能为空！';
					$data['status'] =0;
					$this->response($data,'json');
				}
			}else{
				$data['error'] ='用户不存在，请重试！';
				$data['error_code'] =-1;
				$data['status'] =0;
				$this->response($data,'json');
			}
		}else{
			$data['error'] ='授权过期，请重试！';
			$data['error_code'] =-1;
			$data['status'] =0;
			$this->response($data,'json');
		}
	}
	//我的问题
	public function myquestion(){
		$page = I('post.page','','int')?I('post.page','','int'):1;
		$limit = I('post.limit','','int')?I('post.limit','','int'):10;
		$wei_user_Model=M('user','wei_');
		$users_Model=M('users');
		$posts_Model=M('posts');
		$answers_Model=M('answers');
		
		$wei_user_Data=$wei_user_Model->where(array('session_key'=>$_POST['session']))->find();
		if($wei_user_Data){
			$users_Data=$users_Model->where(array('wxapp_uid'=>$wei_user_Data['openid']))->find();
			if($users_Data){
				$posts_Data=$posts_Model->where(array('post_author'=>$users_Data['id']))->page($page,$limit)->order('post_date desc')->select();
				if($posts_Data){
					foreach($posts_Data as $key=>$val){
						$posts_Data[$key]['answers']=$answers_Model->where(array('qid'=>$val['id']))->count();
					}
					$data['data']= $posts_Data;
					$data['status'] =1;
					$this->response($data,'json');
				}else{
					$data['error'] ='无数据！';
					$data['status'] =0;
					$this->response($data,'json');
				}
			}else{
				$data['error'] ='用户不存在，请重试！';
				$data['error_code'] =-1;
				$data['status'] =0;
				$this->response($data,'json');
			}
		}else{
			$data['error'] ='授权过期，请重试！';
			$data['error_code'] =-1;
			$data['status'] =0;
			$this->response($data,'json');
		}
	}
	//问题答案详情
	public function question_answerDetail(){
		$wei_user_Model=M('user','wei_');
		$users_Model=M('users');
		$posts_Model=M('posts');
		$answers_Model=M('answers');
		
		$wei_user_Data=$wei_user_Model->where(array('session_key'=>$_POST['session']))->find();
		if($wei_user_Data){
			$users_Data=$users_Model->where(array('wxapp_uid'=>$wei_user_Data['openid']))->find();
			if($users_Data){
				$posts_Data=$posts_Model->where(array('id'=>$_POST['id']))->find();
				if($posts_Data){
					$answers_Data=$answers_Model->where(array('qid'=>$_POST['id']))->select();
					foreach($answers_Data as $key=>$val){
						$answers_Data[$key]['user_name']=$users_Model->where(array('id'=>$val['uid']))->getField('user_nicename');
						$answers_Data[$key]['ctn']=$this->replacePicUrl($val['ctn'],'http://'.$_SERVER['HTTP_HOST']);
					}
					$data['data']['question']= $posts_Data;
					$data['data']['answer']= $answers_Data; 
					$data['status'] =1;
					$this->response($data,'json');
				}else{
					$data['error'] ='问题不存在！';
					$data['status'] =0;
					$this->response($data,'json');
				}
			}else{
				$data['error'] ='用户不存在，请重试！';
				$data['error_code'] =-1;
				$data['status'] =0;
				$this->response($data,'json');
			}
		}else{
			$data['error'] ='授权过期，请重试！';
			$data['error_code'] =-1;
			$data['status'] =0;
			$this->response($data,'json');
		}
	}
	//发送模板消息
	public function sendMessage(){
		$answers_Model=M('answers');
		$users_Model=M('users');
		$posts_Model=M('posts');
		$answers_Data=$answers_Model->where(array('id'=>$_POST['id']))->find();
		$posts_Data=$posts_Model->where(array('id'=>$answers_Data['qid']))->find();
		$users_question_Data=$users_Model->where(array('id'=>$posts_Data['post_author']))->find();
		$users_answer_Data=$users_Model->where(array('id'=>$answers_Data['uid']))->find();
		if($posts_Data['form_id']){
			//发送模板消息
			$request_url='https://'.$_SERVER['HTTP_HOST'].'/Wxapp/Common/sendmessage';
			$request_data=array(
				'touser'=>$users_question_Data['wxapp_uid'],
				'template_id'=>'RQ0ORmThmkAAQSnuk1N5JVkh8C7Yx5vVrPTpbW44UMo',
				'page'=>'pages/my/questionDetail?id='.$answers_Data['qid'],
				'form_id'=>$posts_Data['form_id'],
				'data'=>array(
					'keyword1'=>array('value'=>$posts_Data['post_title'],'color'=>''),//咨询内容
					'keyword2'=>array('value'=>strip_tags($answers_Data['ctn']),'color'=>'#5cb85c'),//咨询结果
					'keyword3'=>array('value'=>$users_answer_Data['user_nicename'],'color'=>''),//答复人
					'keyword4'=>array('value'=>$answers_Data['create_date'],'color'=>'')//答复时间
				),
				'emphasis_keyword'=>''
			);
			$return=https_request($request_url,$request_data,'json');
		}
	}
	//上传
	public function upload(){
		if($_FILES['file']){
			$upload = new \Think\Upload();// 实例化上传类
			$upload->maxSize   =     3145728 ;// 设置附件上传大小
			$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
			$upload->rootPath  =      './data/upload/'; // 设置附件上传根目录
			$upload->savePath  =      ''; // 设置附件上传（子）目录
			// 上传文件 
			$info   =   $upload->uploadOne($_FILES['file']);
			if(!$info){// 上传错误提示错误信息
				$data['error'] =$upload->getError();
				$data['status'] =0;
				$this->response($data,'json');
			}else{// 上传成功 获取上传文件信息
				$data['data'] ='https://'.$_SERVER['HTTP_HOST'].'/data/upload/'.$info['savepath'].$info['savename'];
				$data['status'] =1;
				$this->response($data,'json');
			}
		}else{
			$data['error'] ='请选择图片！';
			$data['status'] =0;
			$this->response($data,'json');
		}
	}
	/**
	 * 替换fckedit中的图片 添加域名
	 * @param  string $content 要替换的内容
	 * @param  string $strUrl 内容中图片要加的域名
	 * @return string 
	 * @eg 
	 */
	private function replacePicUrl($content = null, $strUrl = null) {
		if ($strUrl) {
			//提取图片路径的src的正则表达式 并把结果存入$matches中  
			preg_match_all("/<img(.*)src=\"([^\"]+)\"[^>]+>/isU",$content,$matches);
			$img = "";  
			if(!empty($matches)) {  
			//注意，上面的正则表达式说明src的值是放在数组的第三个中  
			$img = $matches[2];  
			}else {  
			   $img = "";  
			}
			  if (!empty($img)) {  
					$patterns= array();  
					$replacements = array();  
					foreach($img as $imgItem){  
						$final_imgUrl = $strUrl.$imgItem;  
						$replacements[] = $final_imgUrl;  
						$img_new = "/".preg_replace("/\//i","\/",$imgItem)."/";  
						$patterns[] = $img_new;  
					}  
	  
					//让数组按照key来排序  
					ksort($patterns);  
					ksort($replacements);  
	  
					//替换内容  
					$vote_content = preg_replace($patterns, $replacements, $content);
			
					return $vote_content;
			}else {
				return $content;
			}           		
		} else {
			return $content;
		}
	}
}
	