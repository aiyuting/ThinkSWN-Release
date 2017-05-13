<?php
/*
 * 小程序首页
 */
namespace Wxapp\Controller;
use Think\Controller\RestController;
class IndexController extends RestController{
	//获取幻灯片
	public function getswiper(){
		$common_banner_Model=M('common_banner','wei_');
		$common_banner_Data=$common_banner_Model->select();
		
		$data['data']= $common_banner_Data;
		$data['status'] =1;
		$this->response($data,'json');
	}
	//获取菜单
	public function getmenu(){
		$common_menu_Model=M('common_menu','wei_');
		$common_menu_Data=$common_menu_Model->order('sort asc')->select();
		
		$data['data']= $common_menu_Data;
		$data['status'] =1;
		$this->response($data,'json');
	}
}
	