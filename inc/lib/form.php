<?php
if(!class_exists('Axzews3xl_Form'))
{
	class Axzews3xl_Form
	{
		/**
		* Retrun html field for optinos 
		* @var settings array 
		* @return string 
		*/
		static function get_field($settings=array())
		{
			extract($settings);
			if(!isset($name)) return "No name found.";
			if(($type == 'select' || $type == 'checkbox') && (!isset($options) || empty($options)) ) return "Options cannot be blank";
			$type = isset($type) ? strtolower(trim($type)) : 'text';
			$class = isset($class) ? $class : '';
			$id = isset($id) ? $id : '';
			$required = isset($required) && $required ? " required='true' aria-required='true' " : '';
			$placeholder = isset($placeholder) ? trim($placeholder) : '';
			$value = isset($value) ? $value : '';
			$extra = isset($extra) ? $extra : '';
			$show_select = isset($show_select) ? $show_select : false;

			switch ($type) {
				case 'text':
				case 'number':
				case 'email':
				case 'password':
					return "<input type='{$type}' name='{$name}' placeholder='{$placeholder}' value='{$value}' {$required} class='{$class}'  id='{$id}' {$extra}>";
					break;

				case 'checkbox':
					$html = '';
					foreach($options as $k=>$v)
					{
						if(is_array($value))
 							$selected = in_array($k, $value) ? true : false; 
						else 
							$selected = ($k == $value) ? true : false;
						$selected_text =  $selected  ?  " checked='true' " : '';
						$html .= "<span class='input-wrap'><input type='{$type}' name='{$name}' value='{$k}' class='{$class}'  id='{$id}' {$extra} {$selected_text}> {$v} </span> ";
						}
					return $html;
					break;

				case 'textarea':
					return "<textarea name='{$name}' class='{$class}' id='{$id}' {$extra} {$required}>{$value}</textarea>";
					break;

				case 'select':
					$html = "<select name='{$name}'  class='{$class}' id='{$id}' {$required} {$extra}>";
					if($show_select) $html .= "<option value=''>Select</option>";
					foreach($options as $k=>$v)
					{
						$selected = ($k == $value) ? " selected='true' " : '';
						$html .= "<span class='input-wrap'><option value='{$k}' class='{$class}'  id='{$id}' {$extra} {$selected}>{$v}</option></span>";
						}
					$html .= "</select>";
					return $html;
					break;
				
				default:
					# code...
					break;
			}


			}
		}
	}
