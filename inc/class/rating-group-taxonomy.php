<?php
if(!class_exists('WPDRS_Rating_Group_Taxonomy'))
{
	class WPDRS_Rating_Group_Taxonomy extends WPDRS
	{
		public $table = 'wpdrs_rating_group_taxonomy';
		function __construct($id=0)
		{
			parent::__construct($id);
			}



		/**
		* Get post type taxonomy
		* @var type string 
		* @return string
		*/
		static function get_rating_group_type_for_post_type($post_type='')
		{
			$meta_value = get_option(self::get_admin_meta_key());
			if(isset($meta_value['rating_group_type_for_post_type'][$post_type])) return $meta_value['rating_group_type_for_post_type'][$post_type];
			return null;
			}

		/**
		* Get rating group id for specific post type 
		* @var type string 
		* @return string
		*/
		static function get_rating_group_id_for_post_type($post_type)
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT rating_group_id FROM {$wpdb->prefix}wpdrs_rating_group_taxonomy WHERE post_type = %s ", $post_type);
			return (int) $wpdb->get_var($qry);
			}

				/**
		* Get rating group id for specific post type 
		* @var type string 
		* @return string
		*/
		static function get_rating_group_id_for_taxonomy($post_type,$tax_id)
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT rating_group_id FROM {$wpdb->prefix}wpdrs_rating_group_taxonomy WHERE post_type = %s AND tax_id = %d ", $post_type, $tax_id);
			return (int) $wpdb->get_var($qry);
			}

		/**
		* Get rating group for post 
		*/
		static function get_rating_group_id_for_post($post_id)
		{
			$post = get_post($post_id);
			$rating_type = self::get_rating_group_type_for_post_type($post->post_type);
			global $wpdb;
			// Checkng for rating group id exists for post id 
			$qry = $wpdb->prepare("SELECT rating_group_id FROM {$wpdb->prefix}wpdrs_rating_group_taxonomy WHERE post_id = %d ", $post_id);
			$rgid = (int) $wpdb->get_var($qry);
			if($rgid) return $rgid;

			// rating group id for 
			if($rating_type == 'fixed')
			{
				return self::get_rating_group_id_for_post_type($post->post_type);
				}
			else 
			{
				$tax_id = self::get_post_category_id($post_id);
				return self::get_rating_group_id_for_taxonomy($post->post_type, $tax_id);
				}
			}
		}
	}