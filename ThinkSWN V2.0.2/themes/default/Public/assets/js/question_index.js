$(document).ready(function() {
	$('a.comment').click(function(){
		var display=$('.comment-box').css('display');
		if(display=='none'){
			$('.comment-box').css('display','block');
		}else{
			$('.comment-box').css('display','none');
		}
	});
	$("#article_content img,.article-answer .ctn img").each(function(){
		$(this).wrap("<a rel='img_group' href='"+this.src+"'></a>");
	});
	$("a[rel=img_group]").fancybox({
		'transitionIn'		: 'none',
		'transitionOut'		: 'none',
		'titlePosition' 	: 'over',
		'titleFormat'		: function(title, currentArray, currentIndex, currentOpts) {
			return '<span id="fancybox-title-over">Image ' + (currentIndex + 1) + ' / ' + currentArray.length + (title.length ? ' &nbsp; ' + title : '') + '</span>';
		}
	});
});