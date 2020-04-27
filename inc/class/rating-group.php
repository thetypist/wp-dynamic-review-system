<?php 
if(!class_exists('WPDRS_Rating_Group'))
{
	class WPDRS_Rating_Group extends WPDRS
	{
		public $ID;
		public $table = 'wpdrs_rating_groups';

		function __construct($id=0)
		{
			parent::__construct($id);
			}

		/**
		* Get group fields 
		*/
		function get_rating_fields()
		{
			global $wpdb;
			$qry = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wpdrs_rating_group_fields WHERE group_id = %d order by order_id ASC", $this->ID);
			$rows = $wpdb->get_results($qry);
			$response = array();
			$wpdb->flush();
			foreach($rows as $r)
			{
				$response[$r->ID] = $r;
				}
			return $response;
			}

		/**
		* Count fields of a particular group 
		* @return int
		*/
		function count_fields()
		{
			return intval(count($this->get_rating_fields()));
			}

		/**
		* Get maximum rating number 
		*/
		static function get_max_rating()
		{
			}

		/**
		* Get group id for a post 
		*/
		static function get_rating_group_id_for_post($post_id)
		{
			return WPDRS_Rating_Group_Taxonomy::get_rating_group_id_for_post($post_id);	
			}
		}
	}