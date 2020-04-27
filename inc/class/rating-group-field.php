<?php
if(!class_exists('WPDRS_Rating_Group_Field'))
{
	class WPDRS_Rating_Group_Field extends WPDRS
	{
		public $table = 'wpdrs_rating_group_fields';
		function __construct($id=0)
		{
			parent::__construct($id);
			}

		/**
		* Delete a row 
		* @var cond array
		*/
		function delete($cond=array())
		{
			global $wpdb;
			$id = 0;
			if(isset($cond['ID']))
				$id = $cond['ID'];
			else if (isset($cond['id']))
				$id = $cond['id'];
			$wpdrs_r = new WPDRS_Rating();
			$wpdrs_r->delete(array(
				'field_id'	=> $id
				));
			return parent::delete($cond);
			}

		}
	}