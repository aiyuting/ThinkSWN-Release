<?php
namespace User\Controller;

use Common\Controller\MemberbaseController;

class ArticleController extends MemberbaseController {
	
	function _initialize(){
		parent::_initialize();
		$this->articles_model=M("posts");
	}
    // 我的文章
	public function index(){
		$uid=sp_get_current_userid();
		$where=array("post_author"=>$uid,'type'=>'article');
		
		$count=$this->articles_model->where($where)->count();
		
		$page=$this->page($count,20);
		
		$articles=$this->articles_model->where($where)
		->order("post_date desc")
		->limit($page->firstRow . ',' . $page->listRows)
		->select();
		
		$this->assign("page",$page->show("default"));
		$this->assign("articles",$articles);
    	$this->display();
    }
}