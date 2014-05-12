<?php namespace Clayton\Formify\Models;

use \ArrayAccess;
use Clayton\Formify\Interfaces\FormInterface;

abstract class Form implements ArrayAccess, FormInterface {

	protected $fields = [];
		
	public function build($context = NULL)
	{
		throw new Exception('Need to implement method "build() in Form subclass"');
	}
	
	public function fields()
	{
		return $this->fields;
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset)) {
			$this->fields[] = $value;
		} else {
			$this->fields[$offset] = $value;
		}
	}
	
	public function offsetGet($offset)
	{
		return $this->offsetExists($offset) ? $this->fields[$offset] : null;
	}

	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->fields);
	}
	
	public function offsetUnset($offset)
	{
		unset($this->fields[$offset]);
	}	

	public function open()
	{
		return \Form::open();
	}

	public function close()
	{
		$output = '';
		$flat_fields = array_dot($this->fields);

		foreach($flat_fields as $field)
		{
		   if($field->onClose())
		   {
		      $output .= $field->__toString();
		   }
		}
		$output .= \Form::close();
		
		return $output;
	}
	
	public function populate($input)
	{
		$flat_input = array_dot($input->toArray());
		foreach($flat_input as $path => $value)
		{
			if($field = array_get($this->fields, $path))
			{
				$field->value($value);
				array_set($this->fields, $path, $field);
			}
		}
		return $this;
	}
	
	public function values()
	{
		$input = [];
		$flat_fields = array_dot($this->fields);
		//bomb($flat_fields);
		foreach($flat_fields as $path => $field)
		{
			array_set($input, $path, $field->value);
		}
		return $input;
	}
	
	public function __toString()
	{
		$output = $this->open();
		$flat_fields = array_dot($this->fields);
		foreach($flat_fields as $field)
		{
			$output .= $field->__toString();
		}
		
		$output .= $this->close();
		return $output;
	}

}