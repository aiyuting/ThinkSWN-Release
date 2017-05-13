<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class AnswerController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
		$this->answers_model=M("answers");
	}
    // 我的回答
	public function index(){
		$uid=sp_get_current_userid();
		$where=array("uid"=>$uid);
		
		$count=$this->answers_model->where($where)->count();
		
		$page=$this->page($count,20);
		
		$answers=$this->answers_model->where($where)
		->order("create_date desc")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		foreach($answers as $key=>$val){
			$answers[$key]['ctn']=strip_tags($val['ctn']);
			$answers[$key]['ctn']=$answers[$key]['ctn']?$answers[$key]['ctn']:'查看详情';
		}
		$this->assign("page",$page->show("default"));
		$this->assign("answers",$answers);
    	$this->display();
    }
}