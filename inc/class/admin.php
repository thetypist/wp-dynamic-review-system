<?php
if(!class_exists('WPDRS_Admin'))
{
	class WPDRS_Admin extends WPDRS
	{
		function __construct($id=0)
		{
			parent::__construct($id);
			$options = maybe_unserialize(get_option(self::get_admin_meta_key(), array()));
			if(!empty($options))
			{
				foreach($options as $k=>$v)
				{
					$this->$k = $v;
					}
				}
			}

		/**
		* Init 
		*/
		function wp_init()
		{
			add_action('admin_init', array($this,'process_wpdrs_admin_ajax'));
			add_action('admin_enqueue_scripts', array($this,'register_scripts'));
			add_action('admin_menu', array($this,'register_menu'));
			add_action('admin_footer', array($this,'footer'));	
			}

		/**
		* Register Scripts 
		*/
		function register_scripts()
		{
			wp_enqueue_style('wpdrs-admin', WPDRS_URL.'assets/css/admin.css', array(), null, 'all');
			wp_enqueue_script('wpdrs-admin', WPDRS_URL.'assets/js/admin.js', array(), null, true);
			}

		/**
		* Admin AJAX for wpdrs page 
		*/
		function process_wpdrs_admin_ajax()
		{
			if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'process_wpdrs_admin_ajax')
			{
				$url = admin_url().'?page=wpdrs';
				?>
				<script type="text/javascript">
					window.location.reload("<?php echo $url;?>");
				</script>
				<?php 
				}
			}

		/**
		* Register Menu 
		*/
		function register_menu()
		{
			add_menu_page(
				'WP Dynamic Review System',
				'WP Dynamic Review System',
				'manage_options',
				'wpdrs',
				array($this,'admin_page_wpdrs')
				);
			}

		/**
		* Admin page wpdrs 
		*/
		function admin_page_wpdrs()
		{
			?>
			<div class="wpdrs-admin-wrap">
				<div class="left-sidebar"><?php echo $this->get_admin_menu(); ?></div>
				<div class="primary-content" id="poststuff"><?php echo $this->admin_page_wpdrs_content(); ?></div>
				<div class="right-sidebar"><?php echo $this->sidebar(); ?></div>
			</div>
			<?php 
			}

		/**
		* Show admin page wpdrs content 
		* @return string 
		*/
		function admin_page_wpdrs_content()
		{
			$post_types = WPDRS::get_post_types();

			$post_type_setting_fields = array(
				array('type'=>'select', 'name'=>'show_rating[]','label'=>'Show Rating','options'=>array('No','Yes')),
				array('type'=>'select', 'name'=>'rating_type[]','label'=>'Rating Type','options'=>array('No','Yes')),
				);

			$action = trim(strtolower($_REQUEST['wpdrs_action']));
			if(method_exists($this, $action)) return $this->$action();
			}

		/**
		* Add review group 
		*/
		function add_rating_group()
		{
			?>
			<form method="POST" class='add-review-group wpdrs-ajax-form' action='".admin_url()."?page=wpdrs_admin_ajax'>
				<div class='postbox'>
					<h2 class='hndle '>Rating Group Name</h2>
					<div class='inside'>
						<table class="wpdrs-table has-fields">
							<tr>
								<th><legend>Group Name</legend></th>
								<td>
									<fieldset>
										<input type='text' name='group_name' required='true' aria-required='true'>
									</fieldset>									
								</td>
							</tr>
							<tr>
								<th><legend>Rating Number</legend></th>
								<td>
									<fieldset>
										<input type='text' name='max_rating' required='true' aria-required='true' value="5">
									</fieldset>									
								</td>
							</tr>
							<tr>
								<th></th>
								<td><input class="button" type='submit' value='<?php echo __('Submit', 'wpdrs'); ?>'></td>
							</tr>
						</table>
					</div>
				</div>			
				<input type='hidden' name='action' value='wpdrs_admin_ajax'>
				<input type='hidden' name='wpdrs_admin_ajax_action' value='add_rating_group'>
				<input type='hidden' name='_wpnonce' value='<?php echo  wp_create_nonce('wpdrs'); ?>'>
			</form>
			<?php
			}

		/**
		* Edit Rating group 
		*/
		function edit_rating_group()
		{
			global $wpdb;
			$group_id = intval($_REQUEST['group_id']);
			$args = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpdrs_rating_groups WHERE ID = %d ", $group_id);
			$row = $wpdb->get_row($args);
			if(!$row) return "Sorry! Unknow rating group ID.";

			$rg = new WPDRS_Rating_Group($group_id);
			$fields = $rg->get_rating_fields();
			?>
			<form method="POST" class='wpdrs-ajax-form'>
				<div class='postbox'>
					<h2 class='hndle '>Rating Group Name</h2>
					<div class='inside'>
						<table class="wpdrs-table has-fields">
							<tr>
								<th><legend>Group Name</legend></th>
								<td>
									<fieldset>
										<input type='text' name="group_name" value='<?php echo $row->group_name; ?>' required='true' aria-required='true'>
									</fieldset>									
								</td>
							</tr>
							<tr>
								<th><legend>Rating Number</legend></th>
								<td>
									<fieldset>
										<?php echo WPDRS_Form::get_field(array(
											'type'		=> 'select',
											'name'		=> 'max_rating',
											'options'	=> array_combine(array_values(range(1,10)),array_values(range(1,10))),
											'value'		=> $row->max_rating,
											'required'	=> true,
											));?>
									</fieldset>									
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div class='postbox'>
					<h2 class='hndle '>Rating Group Fields</h2>
					<div class='inside'>
						<table class="wpdrs-table has-fields">
							<?php 
								if(!empty($fields))
								{
									foreach($fields as $k=>$v)
									{
										?>
										<tr data-id='<?php echo $v->ID; ?>'>
											<td>
												<fieldset>
													<input type='text' name="field_name[]" value='<?php echo $v->field_name; ?>' required='true' aria-required='true'>
													<input type="hidden" name="field_id[]" value="<?php echo $v->ID; ?>">
												</fieldset>									
											</td>
											<td>
												<fieldset>
													<input type='number' name="order_id[]" value='<?php echo $v->order_id; ?>'>
													<button class="button wpdrs-ajax-btn" data-action='delete_rating_field' data-id='<?php echo $v->ID; ?>'>x</button>
												</fieldset>									
											</td>
										</tr>
										<?php 
										}
									}
								?>
							<tr>
								<td colspan="2" class=""><button class="button add-group-field">Add Row</button></td>
							</tr>								
						</table>
					</div>
				</div>

			<input type='hidden' name='group_id' value='<?php echo $group_id; ?>'>
			<input type='hidden' name='action' value='wpdrs_admin_ajax'>
			<input type='hidden' name='wpdrs_admin_ajax_action' value='update_rating_group'>
			<?php wp_nonce_field('wpdrs'); ?>
			<input type="submit" value="Save" class="button">
			</form>

			<?php
			}


		/**
		* Show review groups 
		*/
		function show_rating_groups()
		{
			global $wpdb;
			$paged = isset($_REQUEST['paged']) ? intval($_REQUEST['paged']) : 0;
			$posts_per_page = 20;
			$limit = $posts_per_page;
			$offset = $paged * $posts_per_page;
			$order = 'DESC';
			$total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpdrs_rating_groups ");
			?>
				<a href='<?php echo admin_url()."?page=wpdrs&wpdrs_action=add_rating_group"; ?>' class=''><button class='button'>Add New Group</button></a>
			<?php 
			if($total == 0)
			{
				?>
				<div class='notice notice-info'><p>No rating groups found.</p></div>
				<?php 
				}
			else 
			{
				$args = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpdrs_rating_groups ORDER BY ID DESC LIMIT %d OFFSET %d ", $limit, $offset);
				$rows = $wpdb->get_results($args);
				?>
			<div class='table-wrap'>
				<table class='wp-list-table widefat fixed striped pages' cellspacing='0' cellpadding='0' border='0'>
					<thead>
						<tr>
							<th>ID</th>
							<th>Name</th>
							<th>No of Fields</th>
							<th>Edit</th>
							<th>Delete</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						foreach($rows as $r)
						{
							$rg = new WPDRS_Rating_Group($r->ID);
							?>
							<tr data-id='<?php echo $r->ID; ?>'>
								<td><?php echo $r->ID; ?></td>
								<td><?php echo $r->group_name; ?></td>
								<td><?php echo $rg->count_fields(); ?></td>
								<td><a href='<?php echo admin_url()."?page=wpdrs&wpdrs_action=edit_rating_group&group_id={$r->ID}" ?>'>Edit</a></td>
								<td><a href='<?php echo admin_url()."?page=wpdrs&wpdrs_action=delete_rating_group&group_id={$r->ID}"?>' class='delete-review-group wpdrs-ajax-btn' data-id='<?php echo $r->ID; ?>' data-action='delete_rating_group'>Delete</a></td>
							</tr>
							<?php } ?>
					</tbody>
				</table>
			</div>
				<?php 
				}
			return $html;
			}

		/**
		* Show post settings to match post type and rating group
		*/
		function show_post_settings()
		{
			?>
			<form method="POST" class="post-type-rating-type-form wpdrs-admin-ajax-form">
				<div class='postbox post-type-container'>
					<h2 class='hndle '>Mapping Posts & Rating Groups</h2>
					<div class='inside'>
						<table class="wpdrs-table has-fields widefat">
							<tr>
								<th><legend>Post Type</legend></th>
								<td>
									<fieldset>
										<?php 
											$args = array(
												'type'		=> 'select',
												'name'		=> 'post_type',
												'class'		=> 'wpdrs-post-type-mapping',
												'options'	=> WPDRS::get_post_types(),
												'show_select'=> true,
												);
											echo WPDRS_Form::get_field($args); 
											?>
									</fieldset>									
								</td>
							</tr>
						</table>
					</div>
				</div>
			</form>
			<?php 
			}

		/**
		* Get property 
		* @var property string 
		*/
		function get_property($property,$default='')
		{
			return property_exists($this, $property) ? $this->$property : $default;
			}

		/**
		* Show admin menu items 
		* @return string 
		*/
		function get_admin_menu()
		{
			$items = array(
				'show_global_settings'	=> 'Global Settings',
				'show_rating_groups' 	=> 'Rating Groups',
				'show_post_settings'	=> 'Post Rating Settings',
				'show_feedback_stats'	=> 'Feedback Stats'
				);
			$html = "<ul class='wpdrs-admin-menu'>";
			foreach($items as $k=>$v)
			{
				$href = admin_url().'?page=wpdrs&wpdrs_action='.$k;
				$html .= "<li><a href='{$href}'>{$v}</a></li>";
				}
			$html .= "</ul>";
			return $html;
			}

		/**
		* Show feedback stats 
		*/
		function show_feedback_stats()
		{
			ob_start();
			global $wpdb;
			$qry = "SELECT COUNT(*) as ftotal, DATE(feedback_time) as fdate FROM {$wpdb->prefix}wpdrs_feedbacks GROUP BY DATE(feedback_time) ";
			$rows = $wpdb->get_results($qry);
			if(empty($rows)) return "No stats found.";
			?> 
			<table class="widefat striped" cellpadding="0" cellspacing="0">
				<tr>
					<th>Date</th>
					<th>Count</th>
				</tr>
				<?php 
				foreach($rows as $row)
				{
					$url = site_url().'/wp-admin/?page=wpdrs&wpdrs_action=show_date_wise_feedbacks&date='.date('Y-m-d', strtotime($row->fdate));
					?> 
					<tr>
						<td><a href="<?php echo $url; ?>"><?php echo date('d-m-y', strtotime($row->fdate)); ?></a></td>
						<td><?php echo intval($row->ftotal); ?></td>
					</tr>
					<?php 
					}
				?>
			</table>
			<?php 
			}

		/**
		* Show date wise feedabcks 
		*/
		function show_date_wise_feedbacks()
		{
			$date = $_REQUEST['date'];
			if(empty($date)) return "No valid date found.";
			$date = date('Y-m-d', strtotime($date));
			global $wpdb;
			$qry = "SELECT * FROM {$wpdb->prefix}wpdrs_feedbacks WHERE DATE(feedback_time) = '{$date}' ORDER BY ID DESC  ";
			$rows = $wpdb->get_results($qry);
			if(empty($rows)) return "No results found.";
			ob_start();
			?>
			<table cellpadding="0" cellpadding="0" class="widefat striped">
				<thead>
					<tr>
						<th>View</th>
						<th>Date</th>
						<th>User</th>
						<th>Feedback</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					foreach($rows as $r)
					{
						$user = get_user_by('ID',$r->author_id);
						$user_name = $user->first_name . ' ' . $user->last_name;
						$url = site_url() . '?p=' . $r->post_id;
						?> 
						<tr>
							<td><a href='<?php echo $url; ?>'>View</a></td>
							<td><?php echo date('d-m-y h:i A', strtotime($r->feedback_time)); ?></td>
							<td><?php echo $user_name; ?></td>
							<td><?php echo $r->feedback; ?></td>
						</tr>
						<?php 
						}
					?>
				</tbody>
			</table>
			<?php 
			$html = ob_get_clean();
			return $html;
			}	

		/**
		* Admin sidebar 
		*/
		function sidebar()
		{
			return "Sidebar";
			}

		/**
		* Footer Content 
		*/
		function footer()
		{
			?> 
			<script type="text/javascript">
				window.ADMIN_AJAX_URL = '<?php echo admin_url('admin-ajax.php'); ?>';
				window.WPDRS_WPNONCE = '<?php echo wp_create_nonce('wpdrs'); ?>';
			</script>
			<?php 
			}

		/**
		* Show global settings 
		*/
		function show_global_settings()
		{
			?>
			<form method="POST" class="global-settings wpdrs-admin-ajax-form">
				<div class="postbox">
					<h2 class="title hndle">Global Settings</h2>
					<div class="inside">
						<div class="table-wrap">
							<table class="widefat striped">

								<!-- Combined ratings -->
								<tr>
									<th colspan="2"><h2 class="subtitle">Combined Ratings Settings</h2></th>
								</tr>
								<tr>
									<th><legend>Show combined ratings</legend></th>
									<td>
										<?php 
											echo WPDRS_Form::get_field(
												array(
													'type'		=> 'select',
													'name'		=> 'combined_ratings_enabled',
													'options'	=> array('yes'=>'Yes','no'=>'No'),
													'value'		=> $this->get_property('combined_ratings_enabled', 'yes')
													)
												);
										?>
									</td>
								</tr>
								<tr>
									<th><legend>Combined ratings title</legend></th>
									<td><input type="text" name="combined_ratings_title" value="<?php echo $this->get_property('combined_ratings_title','Overall Ratings'); ?>"></td>
								</tr>
								<tr>
									<th><legend>Combined ratings position</legend></th>
									<td>
									<?php 
										echo WPDRS_Form::get_field(
											array(
												'type'		=> 'select',
												'name'		=> 'combined_ratings_position',
												'value'		=> $this->get_property('combined_ratings_position','after_content'),
												'options'	=> array('before_content'=>'Before Content','after_content'=>'After Content','before_after_content'=>'Both Before and After Content'),
												'show_select'=>true,
												)
											);
										?>
									</td>
								</tr>
								<!-- user reviews -->
								<tr>
									<th colspan="2"><h2 class="subtitle">Reviews Settings</h2></th>
								</tr>
								<tr>
									<th><legend>Show user reviews</legend></th>
									<td>
										<?php 
											echo WPDRS_Form::get_field(
												array(
													'type'		=> 'select',
													'name'		=> 'user_reviews_enabled',
													'options'	=> array('yes'=>'Yes','no'=>'No'),
													'value'		=> $this->get_property('user_reviews_enabled', 'yes')
													)
												);
										?>
									</td>
								</tr>
								<tr>
									<th><legend>User reviews title</legend></th>
									<td><input type="text" name="user_reviews_title" value="<?php echo $this->get_property('user_reviews_title','User Reviews'); ?>"></td>
								</tr>
								<tr>
									<th><legend>User reviews position</legend></th>
									<td>
									<?php 
										echo WPDRS_Form::get_field(
											array(
												'type'		=> 'select',
												'name'		=> 'user_reviews_position',
												'value'		=> $this->get_property('user_reviews_position','after_content'),
												'options'	=> array('before_content'=>'Before Content','after_content'=>'After Content','before_after_content'=>'Both Before and After Content'),
												'show_select'=>true,
												)
											);
										?>
									</td>
								</tr>

								<!-- Review form -->
								<tr>
									<th colspan="2"><h2 class="subtitle">Review Form Settings</h2></th>
								</tr>
								<tr>
									<th><legend>Show review form</legend></th>
									<td>
										<?php 
											echo WPDRS_Form::get_field(
												array(
													'type'		=> 'select',
													'name'		=> 'review_form_enabled',
													'options'	=> array('yes'=>'Yes','no'=>'No'),
													'value'		=> $this->get_property('review_form_enabled', 'yes')
													)
												);
										?>
									</td>
								</tr>
								<tr>
									<th width="40%"><legend>Enable review for post types</legend></th>
									<td width="60%">
									<?php 
										echo WPDRS_Form::get_field(
											array(
												'type'		=> 'checkbox',
												'name'		=> 'post_types_enabled[]',
												'options'	=> array_combine(self::get_post_types(), array_map('ucwords',self::get_post_types())),
												'required'	=> true,
												'value'		=> $this->get_property('post_types_enabled','post')
												)
											);
									?>
									</td>
								</tr>
								<tr>
									<th><legend>Review form title</legend></th>
									<td><input type="text" name="review_form_title" value="<?php echo $this->get_property('review_form_title','Submit Your Review'); ?>"></td>
								</tr>
								<tr>
									<th><legend>Review form position</legend></th>
									<td>
									<?php 
										echo WPDRS_Form::get_field(
											array(
												'type'		=> 'select',
												'name'		=> 'review_form_position',
												'value'		=> $this->get_property('review_form_position','after_content'),
												'options'	=> array('before_content'=>'Before Content','after_content'=>'After Content','before_after_content'=>'Both Before and After Content'),
												'show_select'=>true,
												)
											);
										?>
									</td>
								</tr>
								<tr>
									<th><legend>Default review status</legend></th>
									<td>
										<?php 
											echo WPDRS_Form::get_field(
												array(
													'type'		=> 'select',
													'name'		=> 'review_default_status',
													'value'		=> $this->get_property('review_default_status','approved'),
													'options'	=> array('approved' => 'Approved', 'pending'=> 'Pending'),
													)
												);
											?>
									</td>
								</tr>
								<tr>
									<th>
										<legend>Guests can submit review</legend>
									</th>
									<td>
										<?php 
											echo WPDRS_Form::get_field(
												array(
													'type'		=> 'select',
													'name'		=> 'guest_post_enabled',
													'options'	=> array('no'=>'No','yes'=>'Yes'),
													'show_select'=> true,
													'value'		=> $this->get_property('guest_post_enabled',0),
													)
												);
										?>
									</td>
								</tr>

								<tr>
									<th>
										<input type="submit" name="Save" class="button">
										<input type="hidden" name="action" value="wpdrs_admin_ajax">
										<input type="hidden" name="wpdrs_admin_ajax_action" value="save_global_settings">
										<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpdrs'); ?>">
									</th>
									<td></td>
								</tr>
							</table>
						</div>
					</div>
				</div>
			</form>
			<?php 
			}
		}
	}
$wpdrs_settings = $wpdrs_admin = new WPDRS_Admin();
$wpdrs_admin->wp_init();