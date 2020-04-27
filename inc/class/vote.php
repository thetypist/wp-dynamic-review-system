<?php
if(!class_exists('WPDRS_Vote'))
{
	class WPDRS_Vote extends WPDRS
	{
		public $table = 'wpdrs_rating_votes';
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
			$qry = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}{$this->table} WHERE feedback_id = %d ", $data['feedback_id']);
			$row = $wpdb->get_row($qry);
			if(!$row) return parent::insert($data);
			return parent::update(
				$data, 
				array(
					'ID'	=> $row->ID
					)
				);
			}
		}
	}