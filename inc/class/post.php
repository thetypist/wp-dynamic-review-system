<?php
if(!class_exists('WPDRS_Post'))
{
	class WPDRS_Post extends WPDRS
	{
		function __construct($id=0)
		{
			parent::__construct($id);
			add_filter('the_content', array($this,'the_content'));
			}

		function init()
		{
			$rg = WPDRS_Rating_Group::get_rating_group_id_for_post($this->ID);
			$this->rating_group = new WPDRS_Rating_Group($rg);
			$this->rating_group_fields = $this->rating_group->get_rating_fields();
			}

		/**
		* Content control 
		*/
		function the_content($content)
		{
			global $wpdrs_settings;
			$settings = $wpdrs_settings;
			if(!is_singular()) return $content;
			$position = $settings->get_property('review_position');
			$top_html = $bottom_html = '';

			// Combined Ratinngs 
			if($settings->get_property('combined_ratings_enabled') == 'yes')
			{
				$combined_ratings_position = $settings->get_property('combined_ratings_position');
				if( $combined_ratings_position == 'before_content')
				{
					$top_html .= $this->show_combined_ratings();
					}	
				else if($combined_ratings_position == 'after_content')
				{
					$bottom_html .= $this->show_combined_ratings();
					}
				else if($combined_ratings_position == 'before_after_content')
				{
					$top_html .= $this->show_combined_ratings();
					$bottom_html .= $this->show_combined_ratings();
					} 
				}

			// User reviews 
			if($settings->get_property('user_reviews_enabled') == 'yes')
			{
				$user_reviews_position = $settings->get_property('user_reviews_position');
				if( $user_reviews_position == 'before_content')
				{
					$top_html .= $this->show_feedbacks();
					}	
				else if( $user_reviews_position == 'after_content')
				{
					$bottom_html .= $this->show_feedbacks();
					}
				else if ( $user_reviews_position == 'before_after_content' )
				{
					$top_html .= $this->show_feedbacks();
					$bottom_html .= $this->show_feedbacks();
					} 
				}
			

			// Rating Forms  
			if($settings->get_property('review_form_enabled') == 'yes')
			{
				$review_form_position = $settings->get_property('review_form_position');
				if($review_form_position == 'before_content')
				{
					$top_html .= $this->show_rating_form();
					}	
				else if( $review_form_position == 'after_content')
				{
					$bottom_html .= $this->show_rating_form();
					}
				else if ( $review_form_position == 'before_after_content')
				{
					$top_html .= $this->show_rating_form();
					$bottom_html .= $this->show_rating_form();
					} 
				}
			return "<div class='wpdrs-content-wrap'>{$top_html}{$content}{$bottom_html}</div>";
			}

		/**
		* Show combined rating 
		*/
		function show_combined_ratings()
		{
			return WPDRS_Rating::show_combined_ratings_for_post($this->ID);
			}

		/**
		* Show rating form 
		*/
		function show_rating_form()
		{
			return WPDRS_Rating::show_rating_form($this->ID);
			}

		/**
		* Show feedbacks 
		*/
		function show_feedbacks()
		{
			global $wpdrs_settings;
			$conds = array();
			$conds['paged'] = max(get_query_var('wpdrs_page'),0);
			$feedbacks = WPDRS_Feedback::get_feedbacks_for_post($this->ID,$conds);
			if(empty($feedbacks)) return null;
			ob_start();
			?>
			<div class="wpdrs-title-wrap">
				<h2 class="wpdrs-title"><?php echo __($wpdrs_settings->get_property('user_reviews_title','User Reviews'), 'wpdrs') ; ?></h2>
				<div class="wpdrs-title-sep"></div>
			</div>
			<?php 
			foreach($feedbacks as $feedback)
			{
				$feedback = new WPDRS_Feedback($feedback);
				include(WPDRS_PATH.'templates/loop-post-feedback.php');
				}
			$html = ob_get_clean();
			return $html;
			}
		}
	}
add_action('template_redirect', 'wpdrs_post');
$wpdrs_post = null;
function wpdrs_post()
{
	global $post, $wpdrs_post, $wpdrs_settings;
	if(empty($wpdrs_settings)) $wpdrs_settings = new WPDRS_Admin();
	$enabled_post_types = $wpdrs_settings->get_property('post_types_enabled');
	if(is_null($enabled_post_types) || !is_array($enabled_post_types)) $enabled_post_types = explode(',', $enabled_post_types);
	if(!in_array($post->post_type, $enabled_post_types)) return null;
	$wpdrs_post = new WPDRS_Post($post);
	}
