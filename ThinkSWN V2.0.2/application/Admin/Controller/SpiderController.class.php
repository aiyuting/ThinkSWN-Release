<?php
namespace Admin\Controller;

use Common\Controller\AdminbaseController;

class SpiderController extends AdminbaseController{
	
	protected $spiders_model;
	
	public function _initialize() {
		parent::_initialize();
		$this->spiders_model = M("spiders");
	}
	
	// 后台蜘蛛访问列表
	public function index(){
		$start_time=I('request.start_time');
		if(!empty($start_time)){
		    $where['dateline']=array(
		        array('EGT',$start_time)
		    );
		}
		$end_time=I('request.end_time');
		if(!empty($end_time)){
		    if(empty($where['dateline'])){
		        $where['dateline']=array();
		    }
		    array_push($where['dateline'], array('ELT',$end_time));
		}
		
		$term=I('request.term');
		if(!empty($term)){
		    $where['name']=array('like',"%$term%");
		}
			
		$this->spiders_model->where($where);
		
		$count=$this->spiders_model->count();
			
		$page = $this->page($count, 20);
			
		$this->spiders_model->where($where)->limit($page->firstRow , $page->listRows)->order("dateline DESC");
		$spiders=$this->spiders_model->select();
		$this->assign("page", $page->show('Admin'));
		$this->assign("formget",array_merge($_GET,$_POST));
		$this->assign("spiders",$spiders);
		$this->display();
	}
	//清空全部数据
	public function delall(){
		$spiders_data=$this->spiders_model->field('id')->select();
		foreach($spiders_data as $key=>$val){
			$isdel=$this->spiders_model->where(array('id'=>$val['id']))->delete();
		}
		$count=$this->spiders_model->field('id')->count();
		if(!$count){
			$this->success('清空成功！');
		}else{
			$this->error('清空失败！');
		}
	}
}