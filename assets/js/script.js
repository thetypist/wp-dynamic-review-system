if (typeof $j === 'undefined'){
	$j = jQuery.noConflict();	
} 
if(typeof preventDefault !== 'function'){
	function preventDefault(e){
		e.preventDefault();
		e.stopPropagation();
	}
}

if(typeof clog !== 'function'){
	function clog(d){
		console.log(d);
	}
}

$j(document).ready(function(){

	// AJAX FORM 
	$j(document).on('submit','.wpdrs-ajax-form',function(e){
		preventDefault(e);

		var data = $j(this).serialize();
		
		$j.ajax({
			url	 		: window.WPDRS_AJAX_URL, 
			data 		: data, 
			dataType 	: 'json',
			method  	: 'POST',
			beforeSend 	: function(){
				clog(data);
				show_wpdrs_loading();
			},
			success 	: function(r){
				clog(r);
				hide_wpdrs_loading();
				show_wpdrs_message('success',r.data);
			}
		})
	})

	//AJAX voting for rating 
	$j(document).on('click','.wpdrs-ajax-vote',function(e){
		preventDefault(e);
		var fid = $j(this).attr('data-feedback-id');
		var vote = $j(this).attr('data-vote');
		var data = "action=wpdrs_ajax&wpdrs_ajax_action=process_vote&feedback_id="+fid+"&vote="+vote+"&_wpnonce="+WPDRS_WPNONCE;
		
		$j.ajax({
			url 		: WPDRS_AJAX_URL,
			data 		: data, 
			dataType 	: 'json',
			method 		: 'POST',
			beforeSend 	: function(){
				clog(data);
			},
			success 	: function(r){
				clog(r);
			}

		})
	})


	// Rating click 
	$j(document).on('click','form .wpdrs-rating-click',function(e){
		var v = $j(this).val();
		clog(v);
		$j(this).parent().find(".wpdrs-hidden-rating").val(v);
	})

	function show_wpdrs_loading()
	{
		var html = "<div class='wpdrs-bottom-loading'></div>";
		$j("body").prepend(html);
		}

	function hide_wpdrs_loading()
	{
		$j(".wpdrs-bottom-loading").slideDown().remove();
		}

	function show_wpdrs_message(status,msg,timeout=3000,reload=true)
	{
		var html = "<div class='wpdrs-bottom-wrap "+status+"'>";
		html += "<div class='bottom-msg-wrap'>"+msg+"</div>";
		html += "</div>";
		$j("body").prepend(html, setTimeout(
			function(){
				$j(".wpdrs-bottom-wrap").slideDown().remove();
				if(reload == true) window.location.reload();
			},
			timeout
			));
		}
})