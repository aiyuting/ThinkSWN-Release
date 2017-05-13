<?php
// +----------------------------------------------------------------------
// | ThinkSWN [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkswn.com All rights reserved.
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class TagsController extends HomebaseController {

	// 前台话题文章/问题列表
	public function index(){
	    $tag_id=I('get.id',0,'intval');
		$tags_model=M('tags');
		$tags=$tags_model->where(array('id'=>$tag_id))->find();
		if(empty($tags)){
		    header('HTTP/1.1 404 Not Found');
		    header('Status:404 Not Found');
		    if(sp_template_file_exists(MODULE_NAME."/404")){
		        $this->display(":404");
		    }
		    return;
		}
		
    	$this->assign($tags);
    	$this->display(":Tag/index");
	}
	// 前台最热话题列表
	public function hot(){
		$info=array(
			'name'=>'热门话题'
		);
		$this->assign($info);
    	$this->display(":tags");
	}
}
