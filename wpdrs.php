<?php
/*
Plugin Name: WP Dynamic Review System
Plugin URI: https://www.thecodist.co
Description: Simple review system with feature of custom crieteria based on post type and post category.
Version: 1.0
Author: Nur Hossain
Author URI: https://www.nhossa.in
License: GPLv2 or later
Text Domain: wpdrs
*/

if(!defined('WPDRS_URL')) define('WPDRS_URL', plugin_dir_url(__FILE__));
if(!defined('WPDRS_PATH')) define('WPDRS_PATH', plugin_dir_path(__FILE__));

if(!class_exists('WPDRS'))
{
	class WPDRS
	{
		public $table;
		public $ID;
		static $unique_key = '_dllvelr934_';
		public static $domain_text = 'wpdrs';

		function __construct($id=0)
		{
			global $wpdb;
			if(is_numeric($id)) 
			{
				$this->ID = $id;
				if(!empty($this->table))
				{
					$qry = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE ID = %d ", $id);
					$row = $wpdb->get_row($qry, ARRAY_A);
					if($row)
					{
						foreach($row as $k=>$v)
						{
							$this->$k = $v;
							}
						}
					}
				}
			else if(is_object($id))
			{
				foreach(get_object_vars($id) as $k=>$v)
				{
					$this->$k = $v;
					}
				}
 			$this->include_class_files();
			$this->init();
			}

		/**
		* WP init 
		*/
		function wp_init()
		{
			register_activation_hook(__FILE__, array($this,'activate_plugin'));
			add_action('init', array($this,'register_scripts'));
			add_action('wp_footer', array($this,'footer'));
			}

		/**
		* Set row values of the ID as properties 
		*/
		function init()
		{

			}


		/**
		* Insert a row 
		* @var data array 
		* @return int | string 
		*/
		function insert($data=array())
		{
			global $wpdb;
			$i = $wpdb->insert(
				$wpdb->prefix.$this->table, 
				$data
				);
			return $i ? $wpdb->insert_id : $wpdb->last_error;
			}

		/**
		* Update a row 
		* @var update array 
		* @var where array 
		* @return int | string 
		*/
		function update($data=array(), $conds=array())
		{
			global $wpdb;
			$u = $wpdb->update(
				$wpdb->prefix.$this->table, 
				$data, 
				$conds
				);
			return $u;
			}

		/**
		* Delete a row 
		*/
		function delete($cond=array())
		{
			global $wpdb;
			$d = $wpdb->delete(
				$wpdb->prefix.$this->table, 
				$cond
				);
			return $d ? $d : $wpdb->last_error;
			}

		/**
		* Include class files 
		*/
		function include_class_files()
		{
			foreach(glob(WPDRS_PATH.'inc/lib/*.php') as $file)
			{
				include_once($file);
				}
			foreach(glob(WPDRS_PATH.'inc/class/*.php') as $file)
			{
				include_once($file);
				}
			}

		/**
		* Get posts by query 
		*/
		function get_entries($qry='')
		{
			global $wpdb;
			return $wpdb->get_results($qry);
			}

		/**
		* Show posts 
		*/
		function show_entries($qry='')
		{
			$entries = $this->get_entries($qry);
			if(empty($entries)) return "No entries found.";

			}

		/**
		* Create tables when plugin is activated 
		*/
		function activate_plugin()
		{
			require_once(ABSPATH.'wp-admin/includes/upgrade.php');
			global $wpdb;
			$charset = $wpdb->get_charset_collate();

			/* Rating Group */
			$rating_groups = "CREATE TABLE {$wpdb->prefix}wpdrs_rating_groups (
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				group_name varchar(100) NOT NULL,
				max_rating int DEFAULT 5, 
				author_id bigint UNSIGNED NULL, 
				record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				) {$charset}";
				dbDelta($rating_groups);
			$index_rating_groups = "CREATE INDEX index_rating_groups ON {$wpdb->prefix}wpdrs_rating_groups (ID, group_name, max_rating, author_id, record_time) ";
				$wpdb->query($index_rating_groups);

			$group_fields = "CREATE TABLE {$wpdb->prefix}wpdrs_rating_group_fields(
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				group_id bigint UNSIGNED NOT NULL, 
				field_name varchar(255) NOT NULL,
				order_id int UNSIGNED,
				record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				){$charset} ";
				dbDelta($group_fields);
			$index_group_fields = "CREATE INDEX index_group_fields ON {$wpdb->prefix}wpdrs_rating_group_fields (ID,group_id,field_name,order_id,record_time)";
				$wpdb->query($index_group_fields);

			$taxonomy = "CREATE TABLE {$wpdb->prefix}wpdrs_rating_group_taxonomy(
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				post_type varchar(100) NULL, 
				post_id bigint UNSIGNED NULL, 
				tax_id bigint UNSIGNED NULL, 
				rating_group_id bigint UNSIGNED NOT NULL, 
				author_id bigint UNSIGNED NOT NULL, 
				record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				){$charset}";
				dbDelta($taxonomy);
			$index_taxonomy = "CREATE INDEX index_taxonomy ON {$wpdb->prefix}wpdrs_rating_group_taxonomy (ID,post_type,post_id,tax_id,rating_group_id,author_id,record_time) ";
				$wpdb->query($index_taxonomy);

			$feedback = "CREATE TABLE {$wpdb->prefix}wpdrs_feedbacks(
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				post_id bigint UNSIGNED NOT NULL, 
				author_id bigint UNSIGNED NULL,
				guest_author_id bigint UNSIGNED NULL, 
				feedback text,
				status varchar(15) DEFAULT 'pending',
				feedback_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
				moderated_by bigint UNSIGNED, 
				moderation_time TIMESTAMP 
				){$charset}";
				dbDelta($feedback);
			$index_feedback = "CREATE INDEX index_feedback ON {$wpdb->prefix}wpdrs_feedbacks (ID,post_id,author_id,status,feedback_time) ";
				$wpdb->query($index_feedback);

			$ratings = "CREATE TABLE {$wpdb->prefix}wpdrs_ratings(
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				feedback_id bigint UNSIGNED NOT NULL, 
				field_id bigint UNSIGNED NOT NULL, 
				rating float UNSIGNED NOT NULL
				){$charset}";
				dbDelta($ratings);
			$index_ratings = "CREATE INDEX index_ratings ON {$wpdb->prefix}wpdrs_ratings (ID,feedback_id,field_id,rating)";
				$wpdb->query($index_ratings);

			$votes = "CREATE TABLE {$wpdb->prefix}wpdrs_rating_votes(
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				feedback_id bigint UNSIGNED NOT NULL, 
				vote boolean DEFAULT NULL, 
				author_id bigint UNSIGNED NOT NULL, 
				record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				){$charset}";
				dbDelta($votes);
			$index_votes = "CREATE INDEX index_votes ON {$wpdb->prefix}wpdrs_rating_votes ON(ID,feedback_id,vote,author_id,record_time) ";
				$wpdb->query($index_votes);

			$guest_authors  = "CREATE TABLE {$wpdb->prefix}wpdrs_guest_authors(
				ID bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY, 
				first_name varchar(256),
				last_name varchar(256),
				email varchar(256),
				meta_data varchar(1000),
				record_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				){$charset}";
				dbDelta($guest_authors);
			$index_guest_authors = "CREATE INDEX index_guest_authors ON {$wpdb->prefix}wpdrs_guest_authors (ID, first_name, last_name, email) ";
				$wpdb->query($index_guest_authors);
			}

		/**
		* Get post types 
		*/
		static function get_post_types()
		{
			$args = array(
				'public'		=> true,
			);
			$post_types = get_post_types($args);
			unset($post_types['attachment']);
			return $post_types;
			}

		/**
		* Get Rating Type options 
		* @return array
		*/
		static function get_rating_type_options()
		{
			return array(
				'fixed'	=> 'Fixed',
				'tax_category'			=> 'Category wise',
				'tax_post_tag'			=> 'Tag wise',
				);
			}

		/**
		* Get all entries as options or array values 
		* @var key string 
		* @var value string 
		* @return array 
		*/
		function get_entries_as_options($key='',$value='')
		{	
			global $wpdb;
			$args = "SELECT * FROM {$wpdb->prefix}{$this->table}";
			$rows = $wpdb->get_results($args, ARRAY_A);
			if(empty($rows)) return array();
			$return = array();
			foreach($rows as $row)
			{
				$k = $row[$key];
				$v = $row[$value];
				$return[$k] = $v;
				}
			return $return;
			}

		/**
		* Get all taxonomy fields for a certain post type
		*/
		static function get_tax_terms($tax_name)
		{
			$args = array(
				'taxonomy'		=> $tax_name,
				'hide_empty'	=> 0,
				);
			$terms = get_terms($args);
			$items = array();
			foreach($terms as $t)
			{
				$items[$t->term_id] = ucwords($t->name);
				}
			return $items;
			}

		/**
		* Get post category form list of categories 
		*/
		static function get_post_category_id($post_id)
		{
			$cats = wp_get_post_categories($post_id);
			return reset($cats);
			}

		/**
		* Register Scripts 
		*/
		function register_scripts()
		{
			wp_enqueue_style('wpdrs-style', WPDRS_URL.'assets/css/style.css', array(), null, 'all');
			wp_enqueue_style('wpdrs-fontawesome-4.7', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', array(), null, 'all');
			wp_enqueue_script('wpdrs-script', WPDRS_URL.'assets/js/script.js', array(), null, true);
			}

		/**
		* Footer content 
		*/
		function footer()
		{
			?> 
			<script type="text/javascript">
				window.WPDRS_WPNONCE = '<?php echo wp_create_nonce('wpdrs'); ?>';
				window.WPDRS_AJAX_URL = '<?php echo admin_url('admin-ajax.php'); ?>';
			</script>
			<?php 
			}

		/**
		* Get posts 
		* @var qry string 
		* @return array 
		*/
		function get_posts($qry='')
		{
			global $wpdb;
			$r = $wpdb->get_results($qry);
			$wpdb->flush();
			return $r;
			}

		/**
		* Show posts 
		* @var qry string 
		* @return string 
		*/
		function show_posts($qry='')
		{
			$posts = $this->get_posts($qry);
			if(empty($posts)) return $this->get_no_post_notification();
			$html .= "<div class='posts-wrap table-{$this->table}'>";
			foreach($posts as $p)
			{
				$html .= $this->show_post($p);
				}
			$html .= "</div>";
			}

		/**
		* Show single post 
		*/
		function show_post($p)
		{
			global $wpdb;
			if(is_numeric($p) && $p > 0) $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE ID = %d ", $p));
			return $p->ID;
			}

		/**
		* Return notificaiton text for no posts 
		* @return string 
		*/
		function get_no_post_notification()
		{
			return __("No posts found.", 'wpdrs');
			}

		/**
		* Show single
		*/
		function show_loop_single()
		{
			}

		/**
		* Get meta key 
		*/
		static function get_admin_meta_key()
		{
			return self::$unique_key.'wpdrs_settings';
			}
		}
	$wpdrs = new WPDRS();
	$wpdrs->wp_init();
	}

/**
* Plugin update checker 
*/
require WPDRS_PATH.'/plugins/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/thetypist/wp-dynamic-review-system/',
	__FILE__,
	'edupress'
);

//Optional: If you're using a private repository, specify the access token like this:
$myUpdateChecker->setAuthentication('3a9c8808e104b7c352afd4209c2293754e6fb30e');