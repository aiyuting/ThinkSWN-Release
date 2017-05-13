<?php
// +----------------------------------------------------------------------
// | ThinkSWN [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkswn.com All rights reserved.
// +----------------------------------------------------------------------
namespace User\Controller;
use Common\Controller\MemberbaseController; 
/**
 * 个人中心
 */
class UserController extends MemberbaseController {
	//我的文章
	public function articles() {
    	$this->display(":index");
    }

}


