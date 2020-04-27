<?php
if(!class_exists('WPDRS_Rating'))
{
	class WPDRS_Rating extends WPDRS
	{
		public $table = 'wpdrs_ratings';
		function __construct($id=0)
		{
			parent::__construct($id);
			}

		/**
		* Insert 
		*/
		function insert($data=array())
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE feedback_id = %d AND field_id = %d ", $data['feedback_id'], $data['field_id']);
			$row = $wpdb->get_row($qry);
			if(!$row) return parent::insert($data);
			return parent::update(
				array(
					'rating'	=> floatval($data['rating'])
				),
				array(
					'ID'		=> $row->ID
					)
				);
			}

		/**
		* Show rating form 
		*/
		static function show_rating_form($post_id=0)
		{
			global $wpdrs_post, $wpdrs_settings;
			if(!$post_id) return null;
			ob_start();
			?>
			<div class="wpdrs-rating-form-wrap" id="wpdrs-rating-form">
				
				<!-- Title -->
				<div class="wpdrs-title-wrap">
					<h2 class="wpdrs-title"><?php echo __($wpdrs_settings->get_property('review_form_title'), 'wpdrs') ; ?></h2>
					<div class="wpdrs-title-sep"></div>
				</div>

				<?php 
				// Return if user not logged in
				if(!is_user_logged_in())
				{
					echo __('Please login first to submit your review.','wpdrs');
					echo "</div>"; 
					$html = ob_get_clean();
					return $html;
					}
				$rating_fields = $wpdrs_post->rating_group_fields;
				$fid = WPDRS_FeedbacK::get_id($post_id, get_current_user_id());
				$wpdrs_f = new WPDRS_FeedbacK($fid);
				$wpdrs_ratings = $wpdrs_f->get_ratings();
				foreach($wpdrs_ratings as $k)
				{
					$wpdrs_ratings[$k->field_id] = $k;
					}
				$max_rating = property_exists($wpdrs_post->rating_group, 'max_rating') ?  $wpdrs_post->rating_group->max_rating : 0;
				?>
 				<form class="wpdrs-ajax-form rating-form">					
					<?php 
					foreach($rating_fields as $field)
					{
						$field_rating = ($fid && isset($wpdrs_ratings[$field->ID])) ? $wpdrs_ratings[$field->ID]->rating : '';
						$uniqid = 'class_'.uniqid();
						?>
						<div class="form-row">
							<divl class="label-wrap">
								<label><?php echo __($field->field_name,'wpdrs'); ?></label>	
							</divl>
							<div class="rating-field-wrap">
								<?php echo self::get_rating_star($field_rating, $max_rating, false); ?>
								<input type="hidden" name="rating_field_id[]" value="<?php echo $field->ID; ?>">
							</div>							
						</div>
						
 						<?php 
 						}
					?>
					<div class="form-row feedback">
						<textarea name="feedback"><?php if(property_exists($wpdrs_f, 'feedback')) echo stripslashes($wpdrs_f->feedback); ?></textarea>						
					</div>
					<input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
					<input type="hidden" name="action" value="wpdrs_ajax">
					<input type="hidden" name="wpdrs_ajax_action" value="insert_rating">
					<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpdrs'); ?>">
					<input type="submit" name="" value="Submit">
				</form>
			</div>
			<?php 
			$html = ob_get_clean();
			return $html;
			}

		/**
		* Get feedback 
		*/
		static function get_feedback_and_rating($feedback_id)
		{
			$feedback = new WPDRS_FeedbacK($feedback_id);
			$ratings = $feedback->get_ratings();
			$response = array();
			$response['feedback'] = $feedback->feedback;
			if(!empty($ratings))
			{
				foreach($ratings as $r)
				{
					$response['ratings'][$r->ID] = $r->rating;
					}
				}
			return $response;
			}

		/**
		* Get rating of a particular item 
		* @var feedback_id int 
		* @var field_id int 
		* @var return int 
		*/
		static function get_rating_field_value($feedback_id,$field_id)
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT rating FROM {$wpdb->prefix}wpdrs_ratings WHERE feedback_id = %d AND field_id = %d ", $feedback_id, $field_id);
			return $wpdb->get_var($qry);			
			}

		/**
		* Get combined ratings for post
		* @var post_id int 
		* @return array 
		*/
		static function get_combined_ratings_for_post($post_id)
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT ID FROM {$wpdb->prefix}wpdrs_feedbacks WHERE post_id  = %d  ", $post_id);
			$results = $wpdb->get_results($qry, ARRAY_A);
			if(empty($results)) return array();
			$feedbacks = array_column($results, 'ID');
			$feedback_ids = rtrim(implode(',', $feedbacks),',');
			$ratings_qry = "SELECT feedback_id, field_id, AVG(rating) AS rating FROM {$wpdb->prefix}wpdrs_ratings WHERE feedback_id IN ({$feedback_ids}) GROUP BY field_id ";
			$ratings = $wpdb->get_results($ratings_qry, ARRAY_A);
			return array_combine(array_column($ratings, 'field_id'), array_map('floatval', array_column($ratings, 'rating')));
			}

		/**
		* Show combined reatings for post 
		* @var post_id int 
		* @return string 
		*/
		static function show_combined_ratings_for_post($post_id)
		{
			global $wpdrs_post,$wpdrs_settings;
			$ratings = self::get_combined_ratings_for_post($post_id);
			if(empty($ratings)) return null;
			ob_start();
			?>		
			<div class="combined-ratings-wrap" itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">
				<div class="wpdrs-title-wrap">
					<h2 class="wpdrs-title"><?php echo __($wpdrs_settings->get_property('combined_ratings_title', 'Overall Ratings'), 'wpdrs'); ?></h2>
					<span class="submit-rating-btn"><a href="#wpdrs-rating-form"><?php echo __('Submit Your Review','wpdrs'); ?></a></span>
					<div class="wpdrs-title-sep"></div>
					<div class="wpdrs-title-note">Based on <span itemprop="reviewCount"> <?php echo WPDRS_Rating::count_total_ratings_for_post($post_id); ?></span> reviews</div>
				</div>
			<?php 
			$max_rating = property_exists($wpdrs_post->rating_group, 'max_rating') ?  $wpdrs_post->rating_group->max_rating : 0;
			foreach($ratings as $k=>$v)
			{
				$field_name = $wpdrs_post->rating_group_fields[$k]->field_name;
				if(empty($field_name)) continue;
				$v = number_format($v, 2);
				?> 
				<div class="combined-rating-item-row">
					<div class="combined-rating-item name"><?php echo $field_name; ?></div>
					<div class="combined-rating-item number"><span class="rating-number"><?php echo $v; ?></div>
					<div class="combined-rating-item star"><?php echo self::get_rating_star($v, $max_rating); ?></div>
				</div>
				<?php 
				}
			?>
			</div>
			<?php 
			$html = ob_get_clean();
			return $html;
			}

		/**
		* Count total ratings for post
		* @var post_id int 
		* @return int 
		*/
		static function count_total_ratings_for_post($post_id)
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}wpdrs_feedbacks WHERE post_id  = %d  ", $post_id);
			return (int) $wpdb->get_var($qry);
			}

		/**
		* Show rating star
		*/
		static function get_rating_star($rating,$max_rating=5,$disabled=true)
		{
			ob_start();
			?>
			<fieldset class="wpdrs-rating">
			<?php 
			$rating = number_format(floatval($rating), 2);
			$uniqid = uniqid();
			$disabled = ($disabled) ?  " disabled='true' " : '' ;
			for($i = $max_rating; $i > 0; $i--)
			{
				$value = number_format($i, 2); 
				$is_checked = ($value === $rating) ?  " checked='true' " : '';
				?>
				<input class="wpdrs-rating-click" type="radio" id="star<?php echo $i.'_'.$uniqid; ?>" name="<?php echo $uniqid; ?>" value="<?php echo $i; ?>" <?php echo $is_checked; echo $disabled; ?>  />
				<label class="rating-full" for="star<?php echo $i.'_'.$uniqid; ?>" title="<?php echo $i; ?>"></label>
				<?php 
				$value = number_format($i - 0.5, 2);
				$is_checked = ($value === $rating) ?  " checked='true' " : '';
				?>
				<input class="wpdrs-rating-click" type="radio" id="star<?php echo $i.'_'.$uniqid; ?>half" name="<?php echo $uniqid; ?>" value="<?php echo $value; ?>" <?php echo $is_checked; echo $disabled; ?> />
				<label class="rating-half" for="star<?php echo $i.'_'.$uniqid; ?>half" title="<?php echo $value; ?>"></label>
				<?php } ?>
			<?php if(! $disabled)
			{
				?> 
				<input class="wpdrs-hidden-rating" type="hidden" name="rating[]" value="<?php echo $rating; ?>">
			<?php } ?>
			
			</fieldset>
			<?php 
			$html = ob_get_clean();
			return $html;
			}

		/**
		* Show loop single 
		*/
		function show_loop_single()
		{
			ob_start();
			include(WPDRS_PATH.'templates/loop-rating.php');
			$html = ob_get_clean();
			return $html;
			}

		}
	}