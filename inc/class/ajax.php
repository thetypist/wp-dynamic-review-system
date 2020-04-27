<?php
if(!class_exists('WPDRS_AJAX'))
{
	class WPDRS_AJAX
	{
		function __construct()
		{
			add_action('wp_ajax_wpdrs_ajax', array($this, 'ajax'));
			add_action('wp_ajax_nopriv_wpdrs_ajax', array($this, 'ajax'));
			}

		/**
		* AJAX processing 
		*/
		function ajax()
		{
			if(!wp_verify_nonce($_REQUEST['_wpnonce'], 'wpdrs'))
				die(json_encode(array(
					'status'		=> 0, 
					'error'			=> 'Security check fails',
				)));
			$method = $_REQUEST['wpdrs_ajax_action'];
			if(!method_exists($this, $method))
				die(json_encode(array(
					'status'		=> 0,
					'error'			=> 'No method found to process the request.',
				)));
			die(json_encode($this->$method()));
			}

		/**
		* Insert rating 
		*/
		function insert_rating()
		{
			$wpdrs_r = new WPDRS_Rating();
			$wpdrs_f = new WPDRS_Feedback();
			$feedback = isset($_REQUEST['feedback']) ? $_REQUEST['feedback'] : null;
			$f_id = $wpdrs_f->insert(array(
				'post_id'		=> $_REQUEST['post_id'],
				'author_id'		=> get_current_user_id(),
				'feedback'		=> $feedback,
				'feedback_time'	=> current_time('mysql'),
				));
			if(!$f_id)
				return array(
					'status'	=> 0,
					'error'		=> $f_id,
					);
			$response = array();
			$response['feedback_update'] = $f_id;
			for($i=0; $i<count($_REQUEST['rating']); $i++)
			{
				$response['inert_rating'][] = $wpdrs_r->insert(
					array(
						'feedback_id'	=> $f_id,
						'field_id'		=> $_REQUEST['rating_field_id'][$i],
						'rating'		=> floatval($_REQUEST['rating'][$i]),
						)
					);
				}
			$response['status'] = 1;
			$response['data'] = 'Successful!';
			$response['feedback_id'] = $f_id;
			return $response;
			}

		/**
		* Process vote 
		*/
		function process_vote()
		{
			$wpdrs_v = new WPDRS_Vote();
			$r = $wpdrs_v->insert(array(
				'feedback_id'	=> intval($_REQUEST['feedback_id']),
				'vote'			=> intval($_REQUEST['vote']),
				'author_id'		=> get_current_user_id(),
				'record_time'	=> current_time('mysql')
				));
			return array(
				'status'	=> $r ? 1 : 0, 
				'data'		=> 'Saved!',
				'error'		=> $r
				);
			}
		}
	}
$wpdrs_admin_ajax = new WPDRS_AJAX();
