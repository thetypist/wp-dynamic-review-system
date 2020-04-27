<?
	global $wpdrs_post;
	$field = '';
	if(property_exists($wpdrs_post->rating_group_fields[$this->field_id], 'field_name')) $field = $wpdrs_post->rating_group_fields[$this->field_id]->field_name;
	if(empty($field)) return;
	$max_rating = property_exists($wpdrs_post->rating_group, 'max_rating') ?  $wpdrs_post->rating_group->max_rating : 0;
	?>
	<div class='loop-single rating-row-wrap table-<?php echo $this->table .' row-'.$this->ID; ?>'>
		<div class="rating-name-wrap"><?php echo $field; ?></div>
		<div class="rating-value-wrap"><?php echo self::get_rating_star($this->rating, $max_rating); ?></div>
	</div>

