<?php
// +----------------------------------------------------------------------
// | ThinkSWN [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkswn.com All rights reserved.
// +----------------------------------------------------------------------
namespace Portal\Controller;
use Common\Controller\HomebaseController;

class SitemapController extends HomebaseController {

	public function index(){
		$Etag = md5("thinkswn");
		if(array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER) and $_SERVER['HTTP_IF_NONE_MATCH'] == $Etag){
			header("HTTP/1.1 304 Not Modified");
			exit;
		}else{
			header("Etag:" . $Etag);
		}
		C('SHOW_PAGE_TRACE','');
		$suffix=strtolower(trim(substr(strrchr($_SERVER["REQUEST_URI"], '.'), 1)));
		$posts_Model=M('posts');
		$posts_Data=$posts_Model->order('post_date desc')->limit(5000)->select();
		switch($suffix){
			case 'txt'://http://www.soswen.com/sitemap/index.txt
				foreach($posts_Data as $key=>$val){
					$posts_Data[$key]['loc']=getUrl($val['id']);
				}
				$content=$this->build_txt($posts_Data);
				echo $content;exit;
			break;
			case 'sitemap'://http://www.soswen.com/sitemap/index.sitemap
				$data['loc']=sp_get_host().'/sitemap/index.xml';
				$data['post_date']=$posts_Data[0]['post_date'];
				$content=$this->build_sitemap($data);
				$this->show($content, 'utf-8', 'text/xml'); exit;
			break;
			case 'xml'://http://www.soswen.com/sitemap/index.xml
				foreach($posts_Data as $key=>$val){
					$posts_Data[$key]['loc']=getUrl($val['id']);
				}
				$content=$this->build_xml($posts_Data);
				$this->show($content, 'utf-8', 'text/xml'); exit;
			break;
			default:
				foreach($posts_Data as $key=>$val){
					$posts_Data[$key]['loc']=getUrl($val['id']);
				}
				$content=$this->build_xml($posts_Data);
				$this->show($content, 'utf-8', 'text/xml');exit;
		}
	}
	private function build_txt($data){
		foreach($data as $key=>$val){
			$content.=$val['loc'].'<br>';
		}
		return $content;
	}
	private function build_xml($data){
		$content='<?xml version="1.0" encoding="UTF-8"?>';
		$content.='<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">';
		foreach($data as $key=>$val){
			$content.='<url>
						<loc>'.$val['loc'].'</loc>
						<lastmod>'.$val['post_date'].'</lastmod>
						<priority>0.9</priority>
						<changefreq>daily</changefreq>
					   </url>';
		}
		$content.='</urlset>';
		return $content;
	}
	private function build_sitemap($data){
		$content='<?xml version="1.0" encoding="UTF-8"?>';
		$content.='<sitemapindex>';
		$content.='<sitemap>
					<loc>'.$data['loc'].'</loc>
					<lastmod>'.$data['post_date'].'</lastmod>
				   </sitemap>';
		$content.='</sitemapindex>';
		return $content;
	}
}
