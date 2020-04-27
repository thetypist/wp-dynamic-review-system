<?php
if(!class_exists('WPDRS_Feedback'))
{
	class WPDRS_Feedback extends WPDRS
	{
		public $table = 'wpdrs_feedbacks';
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
			$qry = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE post_id = %d AND author_id = %d ", $data['post_id'], get_current_user_id());
			$row = $wpdb->get_row($qry);
			if(!$row) return parent::insert($data);
			$update = parent::update(
				$data,
				array(
					'ID'	=> $row->ID,
					)
				);
			if($update) return $row->ID;
			return 0;
			}

		/**
		* Get feedback ID for certain post_id and user id 
		* @var post_id int 
		* @var author_id int 
		* @return int 
		*/
		static function get_id($post_id,$author_id)
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT ID FROM {$wpdb->prefix}wpdrs_feedbacks WHERE post_id = %d AND author_id = %d ", $post_id, $author_id);
			$r = (int) $wpdb->get_var($qry);
			$wpdb->flush();
			return $r;
			}

		/**
		* Get ratings of a feedback 
		*/
		function get_ratings()
		{
			global $wpdb;
			$r =  $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpdrs_ratings WHERE feedback_id = %d ", $this->ID));
			$wpdb->flush();
			return $r;
			}

		/**
		* Get all feedbacks of a post 
		* @var post_id int 
		* @var posts_per_page int 
		* @var offset int 
		*/
		static function get_feedbacks_for_post($post_id,$conds=array())
		{
			global $wpdb;
			if(!is_array($conds) || is_null($conds)) $conds = explode(',', $conds);
			extract($conds);
			$posts_per_page = isset($posts_per_page) ? intval($posts_per_page) : 5;
			$paged = isset($_REQUEST['wpdrs_paged']) ? intval($_REQUEST['wpdrs_paged']) : 0;
			$offset = isset($offset) ? $offset : $posts_per_page * $paged;
			$order_by = isset($order_by) ? esc_sql($order_by) : 'ID';
			$order = isset($order) ? esc_sql($order) : 'DESC';
			$qry = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpdrs_feedbacks WHERE post_id = %d ORDER BY {$order_by} {$order} LIMIT {$posts_per_page} OFFSET {$offset} ", $post_id);
			$results = $wpdb->get_results($qry);
			$wpdb->flush();
			return $results;
			}

		/**
		* Show single  
		*/
		function show_loop_single()
		{
			ob_start();
			include(WPDRS_PATH.'templates/loop-feedback.php'); 
			$html = ob_get_clean();
			return $html;
			}
		}	
	}