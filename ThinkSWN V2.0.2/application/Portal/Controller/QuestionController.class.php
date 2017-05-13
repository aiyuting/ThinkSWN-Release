<?php
// +----------------------------------------------------------------------
// | ThinkSWN [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkswn.com All rights reserved.
// +----------------------------------------------------------------------
namespace Portal\Controller;

use Common\Controller\HomebaseController;

class QuestionController extends HomebaseController {
    //问题详情页
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
		
    	
		$comments_model= M("Comments");
		$comments=$comments_model->where(array('post_id'=>$article_id))->select();
		$answers_model= M("Answers");
		$answers=$answers_model->where(array('qid'=>$article_id))->order('isbest desc,likes desc,unlikes asc')->select();
		$users_model= M("Users");
		foreach($answers as $key=>$val){
			$answers[$key]['avatar']=U('user/public/avatar',array('id'=>$val['uid']));
			$answers[$key]['username']=$users_model->where(array('id'=>$val['uid']))->getField('user_nicename');
			$answers[$key]['ctn']=content_nofollow_alt($answers[$key]['ctn'],sp_get_host(),$article['post_title'].'答案');
		}
		
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
		$this->assign("comments",$comments);
		$this->assign("answers",$answers);
    	
    	$this->display(":Question/index");
    }
    // 问题点赞
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
    //提问页
	public function create() {
        $this->check_login();
        $this->_getTermTree();
		$to_user = I('get.to_user_id',0,'intval');
		$users_model=M('Users');
		$coin=$users_model->where(array('id'=>sp_get_current_userid()))->getField('coin');
		
		$this->assign("to_user",$to_user);
		$this->assign("coin",$coin);
        $this->display();
    }
    // 前台用户添加问题提交
    public function add_post(){
        if(IS_POST){
            $this->check_login();
            $terms_model=M('Terms');
			$terms_exist=$terms_model->where(array("term_id"=>intval($_POST['term'])))->find();
            if(empty($_POST['term'])||!$terms_exist){
                $this->error("请至少选择一个分类！");
            }
            if(empty($_POST['post']['post_title'])){
                $this->error("问题不能为空！");
            }
            $posts_model=M('Posts');
            $term_relationships_model=M('TermRelationships');
			
            $_POST['smeta']['thumb'] = sp_asset_relative_url($_POST['smeta']['thumb']);
            
            $_POST['post']['post_date']=date("Y-m-d H:i:s",time());
            $_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
            $_POST['post']['post_author']=sp_get_current_userid();
            $question=I("post.post");
            $question['smeta']=json_encode($_POST['smeta']);
            $question['post_content']=safe_html(htmlspecialchars_decode($question['post_content']));
			$question['type']=$_POST['type'];
			$question['to_user']=$_POST['to_user'];
			$question['reward']=$question['reward']?$question['reward']:0;
			
			$users_model=M('Users');
			$coin=$users_model->where(array('id'=>sp_get_current_userid()))->getField('coin');
			if($question['reward']&&$question['reward']<0){
				$this->error("金币不能为负数！");
			}
			if($question['reward']>$coin){
				$this->error("您剩余的金币不够！");
			}
			if($posts_model->field('post_date,post_author,post_content,post_title,post_modified,smeta,to_user,type,reward')->create($question)!==false){
				$result=$posts_model->add();
				if ($result){
					$url=getUrl($result);
					baidupush(array($url),'original');//百度主动推送提交
					baidupush(array($url),'mip');//百度主动推送mip提交
					tag_add($question['post_tag'],$result);//话题增加
					$result=$term_relationships_model->add(array("term_id"=>intval($_POST['term']),"object_id"=>$result));
					if($result){
						$users_model->where(array('id'=>sp_get_current_userid()))->setDec('coin',$question['reward']);
						$this->success("问题发布成功！");
					}else{
						$posts_model->delete($result);
						$this->error("问题发布失败！");
					}
				}else{
					$this->error("问题发布失败！");
				}
			}else{
				$this->error($posts_model->getError());
			}
        }
    }
    // 前台用户添加答案提交
    public function add_answer(){
        if(IS_POST){
            $this->check_login();
            $answers_model=M('Answers');
            $answer['ctn']=safe_html(htmlspecialchars_decode($_POST['answer']));
			$answer['qid']=intval($_POST['qid']);
			$answer['uid']=sp_get_current_userid();
			$answer['create_date']=date("Y-m-d H:i:s",time());
            if($answers_model->field('ctn,qid,uid,create_date')->create($answer)!==false){
                $result=$answers_model->add();
                if($result){
                    $this->success("提交答案成功！");
                }else{
                    $this->error("提交答案失败！");
                }
            }else{
                $this->error($answers_model->getError());
            } 
        }
    }
    // 答案点赞
    public function answer_do_like(){
    	$this->check_login();
    	
    	$id = I('get.id',0,'intval');
    	
    	$answers_model=M("Answers");
    	
    	$can_like=sp_check_user_action("answers$id",1);
    	
    	if($can_like){
    		$is_save=$answers_model->save(array("id"=>$id,"likes"=>array("exp","likes+1")));
			if($is_save){
				$this->success("赞好啦！");
			}else{
				$this->error("点赞失败！");
			}
    	}else{
    		$this->error("您已赞过啦！");
    	}
    }
    // 答案点踩
    public function answer_do_unlike(){
    	$this->check_login();
    	
    	$id = I('get.id',0,'intval');
    	
    	$answers_model=M("Answers");
    	
    	$can_like=sp_check_user_action("answers$id",1);
    	
    	if($can_like){
    		$is_save=$answers_model->save(array("id"=>$id,"unlikes"=>array("exp","unlikes+1")));
			if($is_save){
				$this->success("踩好啦！");
			}else{
				$this->error("点踩失败！");
			}
    	}else{
    		$this->error("您已踩过啦！");
    	}
    }
    // 答案采纳
    public function answer_do_best(){
    	$this->check_login();
    	
    	$id = I('get.id',0,'intval');
    	
    	$answers_model=M("Answers");
		$posts_model=M("Posts");
    	$users_model=M('Users');
		
		$qid=$answers_model->where(array("id"=>$id))->getField('qid');
		$post_author=$posts_model->where(array("id"=>$qid))->getField('post_author');
		if($post_author==sp_get_current_userid()||current_user_is_admin()){
			$can_like=sp_check_user_action("answers$id",1);
			if($can_like){
				$is_save=$answers_model->save(array("id"=>$id,"isbest"=>1));
				if($is_save){
					$is_save=$posts_model->save(array("id"=>$qid,"best"=>$id));
					if($is_save){
						$reward=$posts_model->where(array("id"=>$qid))->getField('reward');
						if($reward>0){
							$uid=$answers_model->where(array("id"=>$id))->getField('uid');
							$users_model->where(array('id'=>$uid))->setInc('coin',$reward);
						}
						sendWxappMessage($id);//微信小程序推送通知
						$this->success("答案采纳成功！");
					}else{
						$answers_model->save(array("id"=>$id,"isbest"=>0));
						$this->error("答案采纳失败！");
					}
				}else{
					$this->error("答案采纳失败！");
				}
			}else{
				$this->error("您已采纳过啦！");
			}
		}else{
			$this->error("非法操作！");
		}
    }
    // 前台用户问题编辑
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
            $this->display();
        }else{
            $this->error('您编辑的问题不存在！');
        }
        
    }
    
    // 前台用户问题编辑提交
    public function edit_post(){
        if(IS_POST){
            $this->check_login();

            $posts_model=M('Posts');
            $term_relationships_model=M('TermRelationships');
            
            $_POST['post']['post_modified']=date("Y-m-d H:i:s",time());
            $article=I("post.post");
            $article['post_content']=safe_html(htmlspecialchars_decode($article['post_content']));
            if ($posts_model->field('id,post_author,post_content,post_title,post_modified')->create($article)!==false) {
                $result=$posts_model->where(array('id'=>$article['id'],'post_author'=>sp_get_current_userid()))->save($article);
				$posts_data=$posts_model->where(array('id'=>$article['id'],'post_author'=>sp_get_current_userid()))->find();
                if ($result!==false) {
					$result=$term_relationships_model->where(array("object_id"=>$posts_data['id']))->data(array("term_id"=>intval($_POST['term'])))->save();
					if($result!==false){
						$url=getUrl($article['id']);
						baiduupdate(array($url));//百度主动推送更新
						tag_add($article['post_tag'],$article['id']);//话题增加
						$this->success("问题编辑成功！");
					}else{
                        $this->error("问题编辑失败！");
                    }
                }else{
                    $this->error("问题编辑失败！");
                }
            }else{
                $this->error($posts_model->getError());
            }
            
        }
    }
    // 前台用户问题删除
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
    // 前台用户答案删除
    public function answer_del(){
        $this->check_login();
        $id=I("get.id",0,'intval');
		$answer_model=M('Answers');
		$isbest=$answer_model->where(array('id'=>$id,'isbest'=>1))->find();
		if(!$isbest){
			$del=$answer_model->where(array('id'=>$id,'uid'=>sp_get_current_userid()))->delete();
			if($del){
				$this->success('删除成功！');
			}else{
				$this->error('删除失败！');
			}
		}else{
			$this->error('最佳答案不能删除！');
		}
    }
    // 获取问题分类树结构
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
