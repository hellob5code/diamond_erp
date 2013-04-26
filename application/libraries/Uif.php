<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * User Interface Factory - UIF
 * Create UI elements using CodeIgniter Form Helper,
 * Twitter Bootstrap ready
 * @author Marko Aleksic <psybaron@gmail.com>
 */
class UIF {

	// protected $CI;

	// public function __construct()
	// {
	// 	$this->CI =& get_instance();
	// }

	public static function contentHeader($mainTitle = '', $meta = '')
	{
		$out = '<div class="row-fluid"><div class="span6" id="content-main-title"><h4>'.$mainTitle.'</h4></div>';

		if($meta != '')
		{
			$out .= '<div class="span6 text-right" id="content-main-info">';
			if(is_object($meta))
			{
				$out .= '<p class="muted">#'.$meta->id.'@'.$meta->dateofentry.'</p>';
			}
			else
			{
				$out .= '<p class="muted">'.$meta.'</p>';
			}
			$out .= '</div>';
		}

		$out .= '</div><hr>'; 
		
		return $out;
	}

	public static function linkButton($uri = '', $icon = '', $type = 'primary', $attributes = '')
	{
		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		if ( ! is_array($uri))
		{
			$site_url = ( ! preg_match('!^\w+://! i', $uri)) ? site_url($uri) : $uri;
		}
		else
		{
			$site_url = site_url($uri);
		}

		return '<a href="'.$site_url.'" class="btn btn-'.$type.'" '.$attributes.'><i class="'.$icon.'"></i></a>';
	}

	public static function linkDeleteButton($uri = '')
	{
		return self::linkButton($uri, 'icon-trash', 'danger confirm-delete');
	}

	public static function linkInsertButton($uri = '')
	{
		return self::linkButton($uri, 'icon-file');
	}

	public static function button($icon = '', $type = '', $attributes = '')
	{
		$icon = (string) $icon;
		$type = (string) $type;

		if ($type != '')
		{
			$type = 'btn-'.$type;
		}

		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		return '<button class="btn '.$type.'"'.$attributes.'><i class="'.$icon.'"></i></button>';
	}

	public static function filterButton()
	{
		return self::button('icon-search','primary','type="submit"');
	}

	public static function submitButton()
	{
		return self::button('icon-save','primary','type="submit"');
	}

	public static function linkIcon($uri = '', $icon = '', $attributes = '')
	{
		$icon = (string) $icon;

		if ( ! is_array($uri))
		{
			$site_url = ( ! preg_match('!^\w+://! i', $uri)) ? site_url($uri) : $uri;
		}
		else
		{
			$site_url = site_url($uri);
		}

		if ($icon == '')
		{
			$icon = $site_url;
		}

		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		return '<a href="'.$site_url.'"'.'class="'.$icon.'"'.$attributes.'>'.'&nbsp;'.'</a>';
	}

	public static function staticIcon($icon = '', $attributes = '')
	{
		$icon = (string) $icon;

		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		return '<i class="'.$icon.'"'.$attributes.'>'.''.'</i>';
	}

	public static function date($timestamp = null, $format = '%d/%m/%Y')
	{	
		return (!is_null($timestamp) AND $timestamp != '0000-00-00') ?
				mdate($format,mysql_to_unix($timestamp)) : '-';
	}

	public static function today()
	{
		return mdate('%Y-%m-%d');
	}

	public static function isNull($value = '', $extra = '')
	{
		return ($value != '' OR $value != 0) ? $value.$extra : '-';
	}

	public static function viewIcon($controller = '', $id = '', $method = 'view')
	{
		$uri = $controller.'/'.$method.'/'.$id;
		return '<div class="action-group">'.self::linkIcon($uri,'icon-file-alt').'</div>';
	}	

	public static function actionGroup($controller = '', $id = '', $edit = 'edit', $delete = 'delete')
	{
		$edit = $controller.'/'.$edit.'/'.$id;
		$editIcon = self::linkIcon($edit,'icon-edit');

		$delete = $controller.'/'.$delete.'/'.$id;
		$deleteIcon = self::linkIcon($delete,'icon-trash confirm-delete');

		return '<div class="action-group">'.$editIcon.''.$deleteIcon.'</div>';
	}

	public static function formElement($type = '', $label = '', $name = '', $value = '', $attributes = '')
	{
		$out = '';

		if ($attributes != '')
		{
			$attributes = _parse_attributes($attributes);
		}

		switch ($type) {
			case 'text':
				$out .= form_input($name,set_value($name,($value) ? $value->$name : ''),$attributes);
				break;
			case 'password':
				$out .= form_password($name,set_value($name,($value) ? $value->$name : ''),$attributes);
				break;
			case 'dropdown':
				$out .= form_dropdown($name,(isset($value[0])) ? $value[0] : [],
					set_value($name,(isset($value[1])) ? $value[1]->$name : ''),$attributes);
				break;
			case 'textarea':
				$out .= form_textarea($name,set_value($name,($value) ? $value->$name : ''),$attributes);
				break;
			case 'radio':
				if(self::_isAssoc($value[0]))
				{
					$keys = array_keys($value[0]);
					$labels = array_values($value[0]);
					foreach ($keys as $key=>$v) 
					{
						$out .= '<label class="radio inline">';
						$out .= $labels[$key].'<input type="radio" name="'.$name.'" value="'.$v.'"'.
								set_radio($name,$v,(isset($value[1])AND($value[1]!=='')) ? 
								($v==$value[1]->$name) ? true : false : '' ).'/>';
						$out .= '</label>';
					}
				}
				else 
				{
					foreach ($value[0] as $v) 
					{
						$out .= '<label class="radio inline">';
						$out .= $v.'<input type="radio" name="'.$name.'" value="'.$v.'"'.
								set_radio($name,$v,(isset($value[1])AND($value[1]!=='')) ? 
								($v==$value[1]->$name) ? true : false : '' ).'/>';
						$out .= '</label>';
					}
				}
				break;
			case 'checkbox':
				if(is_array($value[0]))
				{	
					foreach ($value[0] as $v) 
					{
						$out .= '<label class="checkbox">';
						$out .= $v.' <input type="checkbox" name="'.$name.'" value="'.$v.'"'.
								set_radio($name,$v,(isset($value[1])AND($value[1]!=='')) ? 
								($v==$value[1]->$name) ? true : false : '' ).'/>';
						$out .= '</label>';
					}
				}
				else
				{
					$out .= '<label class="checkbox">';
					$out .= '<input type="checkbox" name="'.$name.'" value="'.$value[0].'"'.
							set_checkbox($name,$value[0],(isset($value[1])AND($value[1]!=='')) ? 
							($value[0]==$value[1]->$name) ? true : false : '' ).'/>';
					$out .= '</label>';
				}
				break;
			case 'datepicker':
				$out .= '<div class="input-append date datepicker" data-date-format="yyyy-mm-dd">';
				$out .= form_input($name,set_value($name,($value !== '') ?
				(is_object($value)) ? $value->$name : $value : ''),$attributes);
				$out .=	'<span class="add-on"><i class="icon-calendar"></i></span></div>';
				break;
			default:
				$out .= '';
				break;
		}

		return $out;
	}

	public static function controlGroup($type = '', $label = '', $name = '', $value = '', $attributes = '')
	{
		$out  = '<div class="control-group">';
		
		if($label != '')
		{
			$out .= '<label class="control-label">'.$label.'</label>';
		}

		$out .= '<div class="controls">';

		$out .= self::formElement($type, $label, $name, $value, $attributes);

		$out .= '</div></div>';

		return $out;
	}

	public static function formPair($type = '', $label = '', $name = '', $value = '', $attributes = '')
	{
		$out  = '';
		
		if($label != '')
		{
			$out .= '<label>'.$label.'</label>';
		}

		$out .= self::formElement($type, $label, $name, $value, $attributes);

		//$out .= '<span class="help-block">'.$label.'</span>';

		return $out;
	}

	/**
	 * Currency Formater
	 * Formats given number (integer or decimal)
	 * into country specific monetary format w/ or wo/ currency
	 */
	public static function cf($number = 0, $decimalSeparator = '.', $thousandsSeparator = ' ', $precission = 2)
	{
		return number_format($number, $precission, $decimalSeparator, $thousandsSeparator);
	}

	/**
	 * Resource Loader
	 * - loads partials from views/includes folder by default
	 * TODO: Add paramenet to enabling load to pass data object to view
	 */
	public static function load($resource = '', $folder = 'includes')
	{
		$CI =& get_instance();
		return $CI->load->view($folder.'/'.$resource);
	}

	/**
	 * Helper Functions
	 */
	private static function _isAssoc($arr)
	{
	    return (array_keys($arr) !== range(0, count($arr) - 1));
	}

	//////////////
	// TESTING //
	//////////////
	public static function paginate($url = '', $rows = '', $limit, $uriSegment = '')
	{
		$CI =& get_instance();

		$config['base_url'] = site_url($url);
		$config['total_rows'] = $rows;
		$config['per_page'] = $limit;
		$config['uri_segment'] = $uriSegment;
		$config['num_links'] = 3;
		$config['first_link'] = 'Прва';
		$config['last_link'] = 'Последна';

		$CI->pagination->initialize($config);
		
		return $CI->pagination->create_links(); 
	}
}
