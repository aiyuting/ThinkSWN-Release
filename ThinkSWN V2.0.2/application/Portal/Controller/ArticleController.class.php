<?php
// +----------------------------------------------------------------------
// | Soswen [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.Soswen.com All rights reserved.
// +----------------------------------------------------------------------
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class ArticleController extends HomebaseController {
    //文章详情页
	public function index() {
    	$article_id=I('get.id',0,'intval');
		$term_id = M('TermRelationships')->where(array("object_id"=>$article_id,"status"=>1))->getField("term_id");
    	
    	$posts_model=M("Posts");
    	
    	$article=$posts_model
    	->alias("a")
    	->field('a.*,c.user_login,c.user_nicename,b.term_id')
    	->join("__TERM_RELATIONSHIPS__ b ON a.id = b.object_id")
		->join("__USERS__ c ON a.post_author = c.id")
		->where(array('a.id'=>$article_id,'b.term_id'=>$term_id))
		->find();
    	
    	if(empty($article)){
    	    header('HTTP/1.1 404 Not Found');
    	    header('Status:404 Not Found');
    	    if(sp_template_file_exists(MODULE_NAME."/404")){
    	        $this->display(":404");
    	    }
    	    return;
    	}
    	
    	$terms_model= M("Terms");
    	$term=$terms_model->where(array('term_id'=>$term_id))->find();
    	
    	$posts_model->where(array('id'=>$article_id))->setInc('post_hits');
    	
    	$article_date=$article['post_date'];
    	
    	$join = '__POSTS__ as b on a.object_id =b.id';
    	$join2= '__USERS__ as c on b.post_author = c.id';
    	
    	$term_relationships_model= M("TermRelationships");
    	
    	$next=$term_relationships_model
    	->alias("a")
    	->join($join)->join($join2)
    	->where(array('b.id'=>array('gt',$article_id),"post_date"=>array("egt",$article_date),"a.status"=>1,'a.term_id'=>$term_id,'post_status'=>1))
    	->order("post_date asc,b.id asc")
    	->find();
    	
    	$prev=$term_relationships_model
    	->alias("a")
    	->join($join)->join($join2)
    	->where(array('b.id'=>array('lt',$article_id),"post_date"=>array("elt",$article_date),"a.status"=>1,'a.term_id'=>$term_id,'post_status'=>1))
    	->order("post_date desc,b.id desc")
    	->find();
    	
    	$this->assign("next",$next);
    	$this->assign("prev",$prev);
    	
    	$smeta=json_decode($article['smeta'],true);
    	$content_data=sp_content_page($article['post_content']);
    	$article['post_content']=content_nofollow_alt($content_data['content'],sp_get_host(),$article['post_title']);
    	
		$tags_id = explode(",",$article['tags_id']);
		$tags_model=M('Tags');
		foreach($tags_id as $key=>$val){
			$name[$key]=$tags_model->where(array('id'=>$val))->getField('name');
			if($name[$key]){
				$article['post_tags'][]=array('id'=>$val,'name'=>$name[$key]);		
			}
		}
		$article['post_excerpt']=strip_tags($article['post_content']);

    	$this->assign("page",$content_data['page']);
    	$this->assign($article);
    	$this->assign("smeta",$smeta);
    	$this->assign("term",$term);
    	$this->assign("article_id",$article_id);
    	
    	$this->display(":Article/index");
    }
    // 文章点赞
    public function do_like(){
    	$this->check_login();
    	
    	$id = I('get.id',0,'intval');//posts表中id
    	
    	$posts_model=M("Posts");
    	
    	$can_like=sp_check_user_action("posts$id",1);
    	
    	if($can_like){
    		$posts_model->save(array("id"=>$id,"post_like"=>array("exp","post_like+1")));
    		$this->success("赞好啦！");
    	}else{
    		$this->error("您已赞过啦！");
    	}
    }
    // 前台用户添加文章
    public function add(){
        $this->check_login();
        $this->_getTermTree();
        $this->display();
    }
    // 前台用户添加文章提交
    public function add_post(){
        if(IS_POST){
            $this->check_login();
            $terms_model=M('Terms');
			$terms_exist=$terms_model->where(array("term_id"=>intval($_POST['term'])))->find();
            if(empty($_POST['term'])||!$terms_exist){
                $this->error("请至少选择一个分类！");
            }
            if(empty($_POST['post']['post_title'])){
                $this->error("标题不能为空！");
            }
            $posts_model=M('Posts');
			$term_relationships_model=M('TermRelationships');
            
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            
            $_POST['post']['post_date']=date("Y-m-d H:i:s",time());
            $_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
            $_POST['post']['post_author']=sp_get_current_userid();
            $article=I("post.post");
            $article['smeta']=json_encode($_POST['smeta']);
            $article['post_content']=safe_html(htmlspecialchars_decode($article['post_content']));
			$article['type']=$_POST['type'];

            if ($posts_model->field('post_date,post_author,post_content,post_title,post_modified,smeta,type')->create($article)!==false) {
                $result=$posts_model->add();
                if ($result) {
					$url=getUrl($result);
					baidupush(array($url),'original');//百度主动推送提交
					baidupush(array($url),'mip');//百度主动推送mip提交
					tag_add($article['post_tag'],$result);//话题增加
			
                    $result=$term_relationships_model->add(array("term_id"=>intval($_POST['term']),"object_id"=>$result));
                    if($result){
                        $this->success("文章添加成功！");
                    }else{
                        $posts_model->delete($result);
                        $this->error("文章添加失败！");
                    }
                
                } else {
                    $this->error("文章添加失败！");
                }
            }else{
                $this->error($posts_model->getError());
            }
            
            
        }
    }
    
    // 前台用户文章编辑
    public function edit(){
        $this->check_login();
        $id=I("get.id",0,'intval');
        $terms_model=M('Terms');
        $posts_model=M('Posts');
        
        $term_relationship = M('TermRelationships')->where(array("object_id"=>$id,"status"=>1))->getField("term_id",true);
        $this->_getTermTree($term_relationship);
        $post=$posts_model->where(array('id'=>$id,'post_author'=>sp_get_current_userid()))->find();
        if(!empty($post)){
			$tags_id = explode(",",$post['tags_id']);
			$tags_model=M('Tags');
			foreach($tags_id as $key=>$val){
				$post_tag[]=$tags_model->where(array('id'=>$val))->getField('name');
			}
			$post['post_tag']=implode(",",$post_tag);
            $this->assign("post",$post);
            $this->assign("smeta",json_decode($post['smeta'],true));
            $this->display();
        }else{
            $this->error('您编辑的文章不存在！');
        }
        
    }
    
    // 前台用户文章编辑提交
    public function edit_post(){
        if(IS_POST){
            $this->check_login();

            $posts_model=M('Posts');
            $term_relationships_model=M('TermRelationships');
            
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            $_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
            $article=I("post.post");
            $article['smeta']=json_encode($_POST['smeta']);
            $article['post_content']=safe_html(htmlspecialchars_decode($article['post_content']));
            if ($posts_model->field('id,post_author,post_content,post_title,post_modified,smeta')->create($article)!==false) {
                $result=$posts_model->where(array('id'=>$article['id'],'post_author'=>sp_get_current_userid()))->save($article);
				$posts_data=$posts_model->where(array('id'=>$article['id'],'post_author'=>sp_get_current_userid()))->find();
                if ($result!==false) {
					$result=$term_relationships_model->where(array("object_id"=>$posts_data['id']))->data(array("term_id"=>intval($_POST['term'])))->save();
					if($result!==false){
						$url=getUrl($article['id']);
						baiduupdate(array($url));//百度主动推送更新
						tag_add($article['post_tag'],$article['id']);//话题增加
						$this->success("文章编辑成功！");
					}else{
                        $this->error("文章编辑失败！");
                    }
                }else{
                    $this->error("文章编辑失败！");
                }
            }else{
                $this->error($posts_model->getError());
            }
            
        }
    }
    // 前台用户文章删除
    public function del(){
        $this->check_login();
        $id=I("get.id",0,'intval');
        
        $del=M('Posts')->where(array('id'=>$id,'post_author'=>sp_get_current_userid()))->delete();
        if($del){
			M('TermRelationships')->where(array("object_id"=>$id,"status"=>1))->delete();
			$url=getUrl($id);
			baidudel(array($url));//百度主动推送删除
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    // 获取文章分类树结构
    private function _getTermTree($term=array()){
        $result =M('Terms')->order(array("listorder"=>"asc"))->select();
    
        $tree = new \Tree();
        $tree->icon = array('&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ ');
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        foreach ($result as $r) {
            $r['str_manage'] = '<a href="' . U("AdminTerm/add", array("parent" => $r['term_id'])) . '">添加子类</a> | <a href="' . U("AdminTerm/edit", array("id" => $r['term_id'])) . '">修改</a> | <a class="js-ajax-delete" href="' . U("AdminTerm/delete", array("id" => $r['term_id'])) . '">删除</a> ';
            $r['visit'] = "<a href='#'>访问</a>";
            $r['taxonomys'] = $this->taxonomys[$r['taxonomy']];
            $r['id']=$r['term_id'];
            $r['parentid']=$r['parent'];
            $r['selected']=in_array($r['term_id'], $term)?"selected":"";
            $r['checked'] =in_array($r['term_id'], $term)?"checked":"";
            $array[] = $r;
        }
    
        $tree->init($array);
        $str="<option value='\$id' \$selected>\$spacer\$name</option>";
        $terms = $tree->get_tree(0, $str);
        $this->assign('terms', $terms);
    }
}
