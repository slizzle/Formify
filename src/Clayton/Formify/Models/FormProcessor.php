<?php namespace Clayton\Formify\Models;

use \Exception;
use \Validator;
use \Clayton\Formify\Models\FieldType;
use Clayton\Formify\Interfaces\FormProcessorInterface;


abstract class FormProcessor implements FormProcessorInterface {

	/**
	 * Validator object.
	 *
	 * @var object
	 */
	protected $validator;

	/**
	 * Array of extra data.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Array of validating input.
	 *
	 * @var array
	 */
	protected $input;

	/**
	 * Array of rules.
	 *
	 * @var array
	 */
	public $rules = array();

	/**
	 * Array of messages.
	 *
	 * @var array
	 */
	public $messages = array();

	/**
	 * Create a new validation service instance.
	 *
	 * @param  array  $input
	 * @return void
	 */
	public function __construct($input = [])
	{
		$this->input = $input;
	}

	private function preDetectRules($form)
	{
		foreach(array_dot($form->fields()) as $path => $field)
		{
			if($field->type == FieldType::Select)
			{
				$allowed = implode(',', array_keys($field->options));
				$rule = array_get($this->rules, $path);
				$rule .= "in:{$allowed}";
				array_set($this->rules, $path, $rule);
			}
		}
		
		// Holy. shit. cool.
		foreach($this->rules as $ruleKey => $rule)
		{
			if(strpos($ruleKey, '*') !== FALSE)
			{
				$parts = explode('.', $ruleKey);
				$pathPrefix = [];
				$pathSuffix = [];
				$prefix = TRUE;
				foreach($parts as $part)
				{
					if($part == '*')
					{
						$prefix = FALSE;
						continue;
					}
					
					if($prefix) $pathPrefix[] = $part;
					else $pathSuffix[] = $part;
				}
				
				$pathPrefix = implode('.', $pathPrefix);
				$pathSuffix = implode('.', $pathSuffix);
				foreach(array_get($form, $pathPrefix) as $key => $fields)
				{
					$fieldPath = $pathPrefix.'.'.$key.'.'.$pathSuffix;
					array_set($this->rules, $fieldPath, $rule);
				}
				unset($this->rules[$ruleKey]);				
			}
		}
		
	}

	/**
	 * Validates the input.
	 *
	 * @throws ValidateException
	 * @return void
	 */
	public function validate($input, $form)
	{
		$this->preDetectRules($form);

		//expose($input);
		//expose(array_dot($input));
		//expose($this->rules);
		//expose(array_dot($this->rules));
		//bomb();

		$this->validator = Validator::make(array_dot($input), array_dot($this->rules), $this->messages);

		if(!$this->validator->passes())
		{
			throw new ValidationException($this->validator);
		}
	}

	/**
	 * Called upon failure to validate.
	 *
	 *
	 */
	public function failed($input, $form, $object = NULL)
	{
		//.. Should be overridden by subclasses
	}

	/**
	 * Finishes the form submission.
	 *
	 * @throws ValidateException
	 * @return void
	 */
	public function finish($input, $form, $object = NULL)
	{
		//.. Should be overridden by subclasses
	}

	/**
	 * Sets a data key/value on the service.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Gets a data key from the service.
	 *
	 * @param  string  $key
	 * @throws Exception
	 * @return mixed
	 */
	public function __get($key)
	{
		if ( ! isset($this->data[$key]))
		{
			throw new Exception("Could not get [{$key}] from Form\FormProcessor data array.");
		}

		return $this->data[$key];
	}

}