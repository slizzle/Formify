<?php namespace Clayton\Formify\Models;

use Illuminate\Support\Facades\Form as LaravelForm;

!defined('HIDDEN') ? define('HIDDEN', FALSE) : NULL;
!defined('VISIBLE') ? define('VISIBLE', TRUE) : NULL;

abstract class FieldType extends \Clayton\Formify\BasicEnum {
	const Text 			= 'text';
	const TextArea 	= 'textarea';
	const Password 	= 'password';
	const Hidden 		= 'hidden';
	const File 			= 'file';
	const Checkbox		= 'checkbox';
	const Radio			= 'radio';
	const Select		= 'select';
	const Submit		= 'submit';
	const Button		= 'button';
	const Image			= 'image';
}

class Field {

	public $delta = NULL;
	public $type = NULL;
	public $title = NULL;
	public $label = NULL;
	public $value = NULL;
	public $checked = FALSE;
	public $options = [];
	protected $attributes = ['class' => 'form-control'];
	protected $wrapper = [
		'tag' => NULL,
		'attributes' => NULL,
	];
	protected $onClose = FALSE;
	
	// ==========================================
	// -- function __call()
	// * dynamic initializer
	//
	public static function __callStatic($name, $arguments)
	{
		return (new Field())->make($name, $arguments);	
	}
	
	public function make($name, $arguments)
	{
		if(FieldType::isValidValue($name))
		{
			$this->type = $name;
			$this->delta = $arguments[0];
			if(isset($arguments[1])) $this->title = $arguments[1];
			$this->label = $this->title;
			
			if($name == 'button' || $name == 'submit')
			{
				$this->value = $arguments[0];
				$this->value = $arguments[0];
			}
			
			if($name = 'select' && isset($arguments[2]))
			{
				$this->options = $arguments[2];
			}
		}
		
		
		return $this->wrap('div');	
	}
	
	public function attributes($attributes = NULL)
	{
		if(!is_null($attributes) && is_array($attributes))
		{
			if(is_array($this->attributes))
			{
				$this->attributes = array_merge($this->attributes, $attributes);
			}
			else
			{
				$this->attributes = $attributes;
			}
		}
		return $this;
	}
	
	public function placeholder($placeholder = NULL)
	{
		if(is_null($placeholder))
		{
			unset($this->attributes['placeholder']);	
		}
		else
		{
			$this->attributes['placeholder'] = $placeholder;
		}
		return $this;
	}

	public function label($text = NULL)
	{
		if($text == NULL)
		{
			return LaravelForm::label($this->delta, $this->label);
		}
		else
		{
			$this->label = $text;
			return $this;
		}
	}
	
	public function value($value)
	{
		$this->value = $value;
		return $this;
	}
	
	public function checked($checked)
	{
		$this->checked = $checked;
		return $this;
	}
	
	public function options(array $options = NULL)
	{
		if(is_null($options))
		{
			return $this->options;
		}
		else
		{
			$this->options = $options;
			return $this;
		}
	}
	
	// ==========================================
	// -- function wrap()
	//
	public function wrap($tag = 'div', $attributes = NULL)
	{
		if(!is_null($tag))
		{
			$this->wrapper['tag'] = $tag;
			if(is_array($attributes))
			{
				$this->wrapper['attributes'] = $attributes;
			}
			else
			{
				$this->wrapper['attributes'] = $this->defaultWrapperAttributes();
			}
		}
		else
		{
			$this->wrapper['tag'] = NULL;
			$this->wrapper['attributes'] = NULL;
		}
		
		return $this;
	}
	
	// ==========================================
	// -- function defaultWrapperAttributes()
	//
	private function defaultWrapperAttributes()
	{
		return [
			'class' => implode(' ', [
				'form-field',
				'field-type-'.$this->type,
				'field-name-'.$this->delta,
			])
		];
	}

	public function onClose($onClose = NULL)
	{
		if(is_null($onClose))
		{
			return $this->onClose;
		}
		elseif(is_bool($onClose))
		{
			$this->onClose = $onClose;
		}
		
		return $this;
	}
	

	// ==========================================
	// -- function __toString()
	//	
	public function __toString()
	{
		if(!is_null($this->wrapper['tag']))
		{
			extract($this->wrapper);
			$attributeString = '';
			foreach ($attributes as $attribute => $value)
			{
				$attributeString .= $attribute.'="'.$value.'" ';
			}

			$output = "<$tag $attributeString>" . $this->baseOutput() . "</$tag>";
			return $output;
		}
		else
		{
			return $this->baseOutput();
		}
	}
	
	private function baseOutput()
	{
		$output = '';
		switch($this->type) {
			case FieldType::Checkbox:
			case FieldType::Radio:
				$output .= $this->outputField();
				$output .= $this->outputLabel();
				break;
			default:
				$output .= $this->outputLabel();
				$output .= $this->outputField();
				break;
		}
		return $output;
	}
	
	private function outputLabel()
	{
		$output = '';
		if($this->label != NULL)
		{
			$output .= $this->label();
		}
		elseif($this->title != NULL)
		{
			$this->label($this->title);
			$output .= $this->label();
		}
		return $output;
	}
	
	private function outputField()
	{
		$output = '';
		
		switch($this->type) {
			
			case FieldType::Password:
			case FieldType::File:
				$output .= LaravelForm::{$this->type}($this->delta, $this->attributes);
				break;
				
			case FieldType::Checkbox:
			case FieldType::Radio:
				$output .= LaravelForm::{$this->type}($this->delta, $this->value, $this->checked, array_merge($this->attributes, ['id' => $this->delta]));
				break;
				
			case FieldType::Button:
			case FieldType::Submit:
				$output .= LaravelForm::{$this->type}($this->value, $this->attributes);
				break;
				
			case FieldType::Select:
				$output .= LaravelForm::{$this->type}($this->delta, $this->options, $this->value, $this->attributes);
				break;
				
			default:
				$output .= LaravelForm::{$this->type}($this->delta, $this->value, $this->attributes);
				break;
		}
		
			
		return $output;
	}
	
}