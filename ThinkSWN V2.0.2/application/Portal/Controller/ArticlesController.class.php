<?php
// +----------------------------------------------------------------------
// | ThinkSWN [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkswn.com All rights reserved.
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class ArticlesController extends HomebaseController {

	// 前台文章列表
	public function index(){
	    
    	$this->display(":Article/list");
	}
}
