<?php
	$user = get_user_by('ID', $this->author_id);
	$name = $user->first_name .' '. $user->last_name; 
	?>
<div class='loop-single feedback-meta'>
	<span class='feedbacker-name'><?php echo $name; ?></span> 
	<!-- <span class='feedback-status'><?php //echo $this->status; ?></span> -->
	<span class='feedback-time'><?php echo date('d F, y', strtotime($this->feedback_time)); ?></span>
</div>
<div class='loop-single feedback-text table-<?php echo $this->table .' row-'. $this->ID ?>'><?php echo stripcslashes( $this->feedback); ?></div>

