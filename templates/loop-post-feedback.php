<div class='loop-single post-user-feedback row-<?php echo $feedback->ID;?>' id="wpdrs-rating-<?php echo $feedback->ID; ?>">
	<?php 
		echo $feedback->show_loop_single(); 
		$ratings = $feedback->get_ratings(); 
		if(empty($ratings)) return null;
		?>
		<div class="loop-ratings-wrap">
		<?php
			foreach($ratings as $r)
			{
				$rating = new WPDRS_Rating($r);
				echo $rating->show_loop_single();
				} 
			?>
		</div>
</div>
