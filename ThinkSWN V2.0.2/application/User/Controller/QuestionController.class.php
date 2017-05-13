<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class QuestionController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
		$this->articles_model=M("posts");
		$this->answers_model=M("answers");
	}
    // 我的提问
	public function index(){
		$uid=sp_get_current_userid();
		$where=array("post_author"=>$uid,'type'=>'question');
		
		$count=$this->articles_model->where($where)->count();
		
		$page=$this->page($count,20);
		
		$questions=$this->articles_model->where($where)
		->order("post_date desc")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		
		foreach($questions as $key=>$val){
			$questions[$key]['answer_count']=$this->answers_model->where(array('qid'=>$val['id']))->count();
		}
		
		$this->assign("page",$page->show("default"));
		$this->assign("questions",$questions);
    	$this->display();
    }
}