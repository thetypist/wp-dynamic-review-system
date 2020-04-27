if (typeof $j == 'undefined') $j = jQuery.noConflict();

// Prevent Default actions 
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
	
	// Ajax submit 
	$j(document).on('submit','.wpdrs-admin-ajax-formHOLD',function(e){
		preventDefault(e);
		let data = $j(this).serialize();
		$j.ajax({
			url	 		: window.ADMIN_AJAX_URL, 
			data 		: data, 
			dataType 	: 'json',
			method 		: 'post', 
			beforeSend 	: function(){
				clog(data);
			},
			success 	: function(r){
				clog(r);
				if(r.status == 0){
					alert(r.error);
				}
			}
		})
	})

	// Add group field 
	$j(document).on('click','.add-group-field',function(e){
		preventDefault(e);
		var html = "<tr> <td> <fieldset> <input placeholder='Field Name' type='text' name='field_name[]' value='' required='true' aria-required='true'> <input type='hidden' name='field_id[]' value=''> </fieldset> </td><td><fieldset><input placeholder='Order' type='number' name='order_id[]'><button class='button wpdrs-ajax-btn' data-action='delete_rating_field' data-id=''>x</button></fieldset></td></tr>";
		$j(this).before(html);
	})


	// Delete review group 
	$j(document).on('click','.wpdrs-ajax-btn',function(e){
		preventDefault(e);
		let action = $j(this).attr('data-action');
		let ID = $j(this).attr('data-id');
		if( ID == '')
		{
			$j(this).closest("tr").slideUp().remove();
			return false;
			}
		let data = "action=wpdrs_admin_ajax&wpdrs_admin_ajax_action="+action+"&_wpnonce="+window.WPDRS_WPNONCE+"&id="+ID;
		$j.ajax({
			url	 		: window.ADMIN_AJAX_URL, 
			data 		: data, 
			dataType 	: 'json',
			method 		: 'post', 
			beforeSend 	: function(){
				clog(data);
				if(!confirm("Are you sure?")){
					return false;
				}
			},
			success 	: function(r){
				clog(r);
				if(r.status == 0){
					alert(r.error);
				}
				if(action == 'delete_rating_group' || action == 'delete_rating_field'){
					$j("tr[data-id='"+ID+"']").slideUp().remove();
				}
			}
		})
	})

	// Post type change post-rating mapping 
	$j(document).on('change', '.wpdrs-post-type-mapping', function(e){
		preventDefault(e);
		var postType = $j(".wpdrs-post-type-mapping :selected").val();
		var data = "action=wpdrs_admin_ajax&wpdrs_admin_ajax_action=show_rating_option_for_post_type_mapping&post_type="+postType+"&_wpnonce="+WPDRS_WPNONCE;
		$j.ajax({
			url 		: ADMIN_AJAX_URL,
			data 		: data, 
			dataType 	: 'json',
			method 		: 'POST',
			beforeSend	: function(){
				clog(data);
			},
			success 	: function(r){
				clog(r);
				if(r.status == 1){
					if($j(".rating-type-fields-container").length > 0) $j(".rating-type-fields-container").remove();
					if($j(".rating-type-container").length > 0) $j(".rating-type-container").remove();
					$j(".post-type-container").after(r.data);
				}
			}
		})
	})


	// Post type rating type mapping 
	$j(document).on('change','.post_type_rating_type_mapping',function(e){
		preventDefault(e);
		var postType = $j(".wpdrs-post-type-mapping :selected").val();
		var ratingType = $j(".post_type_rating_type_mapping :selected").val();

		var data = "action=wpdrs_admin_ajax&wpdrs_admin_ajax_action=show_post_type_rating_type_fields&_wpnonce="+WPDRS_WPNONCE+"&post_type="+postType+"&rating_type="+ratingType;
		$j.ajax({
			url 		: ADMIN_AJAX_URL,
			data 		: data, 
			dataType 	: 'json',
			method 		: 'POST',
			beforeSend	: function(){
				clog(data);
			},
			success 	: function(r){
				clog(r);
				if(r.status == 1){
					if($j(".rating-type-fields-container").length > 0) $j(".rating-type-fields-container").remove();
					$j(".rating-type-container").after(r.data);
				}
			}
		})
	})
})