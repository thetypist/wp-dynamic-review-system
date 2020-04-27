<?php
if(!class_exists('WPDRS_Admin_AJAX'))
{
	class WPDRS_Admin_AJAX extends WPDRS
	{
		function __construct($id=0)
		{
			parent::__construct($id);
			add_action('admin_init', array($this,'process_form_submit'));
			add_action('wp_ajax_wpdrs_admin_ajax', array($this,'ajax_process'));
			}

		/**
		* Unset unnecessary fields 
		*/
		function unset_unrequired_fields()
		{
			unset($_REQUEST['action']);
			unset($_REQUEST['wpdrs_admin_ajax_action']);
			unset($_REQUEST['_wpnonce']);
			}

		/**
		* process form submit 
		*/
		function process_form_submit()
		{
			if(isset($_REQUEST['action']) && $_REQUEST['action'] === 'wpdrs_admin_ajax')
			{
				$this->process();
				}
			}

		/**
		* Process ajax 
		*/
		function ajax_process()
		{
			die(json_encode($this->process()));
			}

		/**
		* Handle ajax 
		*/
		function process()
		{
			if(!wp_verify_nonce($_REQUEST['_wpnonce'], 'wpdrs'))
				return array(
					'status'	=> 0,
					'error'		=> 'Security check fails.'
				);
			$method = strtolower(trim($_REQUEST['wpdrs_admin_ajax_action']));
			if(!method_exists($this, $method))
				return array(
					'status'	=> 0,
					'error'		=> 'No method found to handle the request.',
				);
			return $this->$method();
			}

		/**
		* Add review group 
		*/
		function add_rating_group()
		{
			global $wpdb;
			$group_name = trim($_REQUEST['group_name']);
			$rg = new WPDRS_Rating_Group();

			$data = array(
					'group_name'	=> $group_name, 
					'author_id'		=> get_current_user_id(),
					'record_time'	=> current_time('mysql'),
					);
			$id = $rg->insert($data);
			
			if($id)
				return array(
				'status'	=> 1,
				'data'		=> 'Successfully added!',
				'ID'		=> $wpdb->insert_id,
				);

			return array(
				'status'	=> 0,
				'error'		=> $wpdb->last_error
				);
			}

		/**
		* Delete review group 
		*/
		function delete_rating_group()
		{
			$id = intval($_REQUEST['id']);
			$rg = new WPDRS_Rating_Group();
			$delete = $rg->delete(
				array(
					'ID' => $id 
					)
				);
			if($delete) 
				return array(
					'status'	=> 1, 
					'data'		=> 'Successfully deleted!',
					'ID'		=> $id,
				);
			return array(
				'status'		=> 0,
				'error'			=> $wpdb->last_error
				);
			}

		/**
		* Update Rating Group Fields 
		*/
		function update_rating_group()
		{
			global $wpdb;
			$group_name = trim($_REQUEST['group_name']);
			$group_id = intval($_REQUEST['group_id']);
			$max_rating = intval($_REQUEST['max_rating']);
			$reply = array();
			$rg = new WPDRS_Rating_Group();
			$reply['update_group'] = $rg->update(
				array(
					'group_name'	=> $group_name,
					'max_rating'	=> $max_rating,
					),
				array(
					'ID'			=> $group_id
					)
				);
			$fields_len = count($_REQUEST['field_name']);
			$rgf = new WPDRS_Rating_Group_Field();
			for($i = 0; $i < count($_REQUEST['field_name']); $i++)
			{
				if(!empty($_REQUEST['field_id'][$i]))
				{
					$update = $rgf->update(
						array(
							'field_name'	=> trim($_REQUEST['field_name'][$i]),
							'order_id'		=> intval($_REQUEST['order_id'][$i])
							),
						array(
							'ID'			=> intval($_REQUEST['field_id'][$i])
							)
						);
					$reply['update_field'][] = $update;
					}
				else 
				{
					$insert = $rgf->insert(
						array(
							'field_name'	=> trim($_REQUEST['field_name'][$i]),
							'group_id'		=> $group_id,
							'order_id'		=> intval($_REQUEST['order_id']),
							)
						);
					if($insert) $reply['insert_field'][] = $wpdb->insert_id;
					}
				}
			return $reply;
			}

		/**
		* Delete Field 
		*/
		function delete_rating_field()
		{
			$id = intval($_REQUEST['id']);
			$rgf = new WPDRS_Rating_Group_Field();
			$delete = $rgf->delete(
				array(
					'ID'		=> $id,
					)
				);
			return array(
				'status'		=> $delete, 
				'data'			=> 'Successfully deleted', 
				'error'			=> $delete,
				);
			}

		/**
		* Show post type option for post-rating mapping 
		*/
		function show_rating_option_for_post_type_mapping()
		{
			$post_type = $_REQUEST['post_type'];
			$rating_type = WPDRS_Rating_Group_Taxonomy::get_rating_group_type_for_post_type($post_type);
			$args = array(
				'type'			=> 'select', 
				'name'			=> 'post_type_rating_type',
				'class'			=> 'post_type_rating_type_mapping',
				'options'		=> WPDRS::get_rating_type_options(),
				'value'			=> $rating_type,
				'show_select'	=> true,
				'required'		=> true,
				);
			$field = WPDRS_Form::get_field($args);
			$data = "<div class='postbox rating-type-container'>
				<div class='inside'>
					<table class='wpdrs-table has-fields'>
						<tr>
							<th><legend>Rating Type</legend></th>
							<td>
								<fieldset>{$field}</fieldset>
							</td>
						</tr>
					</table>
				</div>
			</div>";
			if(!empty($rating_type))
			{
				$options = $this->show_post_type_rating_type_fields($post_type,$rating_type);
				if(isset($options['data'])) $data .= $options['data'];
				}

			return array(
				'status'	=> 1,
				'data'		=> $data,
				);
			}

		/**
		* Show post-type wise rating-type wise rating options 
		*/
		function show_post_type_rating_type_fields($post_type='',$rating_type='')
		{
			if(empty($post_type)) $post_type = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : null;
			if(empty($rating_type)) $rating_type = isset($_REQUEST['rating_type']) ? $_REQUEST['rating_type'] : null;

			$rg = new WPDRS_Rating_Group();
			$rg_options = $rg->get_entries_as_options('ID','group_name');
			$data = "<div class='rating-type-fields-container'>";
			switch($rating_type)
			{
				case 'fixed':
				$args = array(
					'type'			=> 'select',
					'name'			=> 'rating_group_id',
					'options'		=> $rg_options,
					'show_select'	=> true,
					'required'		=> true,
					'value'			=> WPDRS_Rating_Group_Taxonomy::get_rating_group_id_for_post_type($post_type),
					);
				$data .= "<div class='postbox'>
					<!-- <h2 class='hndle '>Set Rating Type</h2> -->
					<div class='inside'>
						<table class='wpdrs-table has-fields'>
							<tr>
								<th><legend>Rating Group</legend></th>
								<td>
									<fieldset>".WPDRS_Form::get_field($args)."</fieldset>
								</td>
							</tr>
						</table>
					</div>
				</div>";
				break;
 				default:
					if(strpos(strtolower($rating_type), 'tax') === FALSE) return 'tax not found.' ;
					$tax = str_replace('tax_', '', $rating_type);
					$terms = WPDRS::get_tax_terms($tax);
					$data .= "<div class='postbox'>";
					$data .= "<h2 class='hndle '>Taxonomy-Rating Grooup Mapping</h2>";
					$data .= "<div class='inside'>";
					$data .= "<table class='wpdrs-table has-fields'>";
					$data .= "<thead>";
					$data .= "<tr>";
					$data .= "<th>".__('Term', 'wpdrs')."</th>";
					$data .= "<th>".__('Rating Group', 'wpdrs')."</th>";
					$data .= "</tr>";
					$data .= "</thead>";
					foreach($terms as $k=>$v)
					{
						$wpdrs_rg = new WPDRS_Rating_Group();
						$args = array(
							'type'			=> 'select',
							'name'			=> 'rating_group_id[]',
							'options'		=> $wpdrs_rg->get_entries_as_options('ID','group_name'),
							'show_select'	=> true,
							'value'			=> WPDRS_Rating_Group_Taxonomy::get_rating_group_id_for_taxonomy($post_type,$k),
							);
						$field = WPDRS_Form::get_field($args);
						$field .= "<input type='hidden' name='tax_id[]' value='{$k}'>";
						$field .= "<input type='hidden' name='tax_post_type[]' value='{$post_type}'>";
						$data .= "<tr>";
						$data .= "<td>{$v}</td>";
						$data .= "<td>{$field}</td>";
						$data .= "</tr>";
						}
					$data .= "</table>";
					$data .= "</div>";
					$data .= "</div>";
				break;
				}
			$data .= "<input type='hidden' name='action' value='wpdrs_admin_ajax'>";
			$data .= "<input type='hidden' name='wpdrs_admin_ajax_action' value='save_post_type_rating_type_taxonomy'>";
			$data .= "<input type='hidden' name='_wpnonce' value='".wp_create_nonce('wpdrs')."'>";
			$data .= "<input type='submit' name='' value='Save' class='button'>";
			$data .= "</div>";
			return array(
				'status'	=> 1,
				'data'		=> $data,
				);
			}

		/**
		* Save global settings 
		*/
		function save_global_settings()
		{
			$meta_key = self::get_admin_meta_key();
			$meta_value = get_option($meta_key, array());
			foreach($_REQUEST as $k=>$v)
			{
				$meta_value[$k] = $v;
				}
			$u = update_option($meta_key,$meta_value);
			return array(
				'status'	=> $u, 
				'data'		=> "Saved!"
				);
			}

		/**
		* Save taxonomy post type - rating type mapping 
		*/
		function save_post_type_rating_type_taxonomy()
		{

			$post_type = trim($_REQUEST['post_type']);
			$rating_group_type = trim(strtolower($_REQUEST['post_type_rating_type']));
			
			// Updating wp_option to save rating group type for post type 
			$mk = WPDRS_Rating_Group_Taxonomy::get_admin_meta_key();
			$mv = get_option($mk, array());
			$mv['rating_group_type_for_post_type'][$post_type] = $rating_group_type;
			$update_mk = update_option($mk,$mv);

			// Deleting all settings for the post type 
			$wpdrs_rgt = new WPDRS_Rating_Group_Taxonomy();
			$delete_pt = $wpdrs_rgt->delete(
				array(
					'post_type'	=> $post_type
					)
				);

			// Inserting new settings 
			switch($rating_group_type)
			{
				case 'fixed':
					$insert = $wpdrs_rgt->insert(
						array(
							'post_type'			=> $post_type,
							'rating_group_id'	=> intval($_REQUEST['rating_group_id']),
							'author_id'			=> get_current_user_id(),
							'record_time'		=> current_time('mysql')
							)
						);
				break;
				default:
					if(strpos($rating_group_type, 'tax_') === FALSE) return null;
					$insert = array();
					for($i = 0; $i < count($_REQUEST['rating_group_id']); $i++)
					{
						$insert[] = $wpdrs_rgt->insert(
							array(
								'post_type'			=> $_REQUEST['tax_post_type'][$i],
								'tax_id'			=> $_REQUEST['tax_id'][$i],
								'rating_group_id'	=> $_REQUEST['rating_group_id'][$i],
								'author_id'			=> get_current_user_id(),
								'record_time'		=> current_time('mysql'),
								),
							array(
								"%s",
								"%d",
								"%d",
								"%d",
								"%s"
								)
							);
						}
				break;
				}
			return array(
				'status'	=> 1,
				'data'		=> 'Updated!',
				'update_mk'	=> $update_mk,
				'meta_key'	=> $mk,
				'delete_pt'	=> $delete_pt,
				'insert'	=> $insert,
				);
			}
		}
	}
$wpdrs_admin_ajax = new WPDRS_Admin_AJAX();