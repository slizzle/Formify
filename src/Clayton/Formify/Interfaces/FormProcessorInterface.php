<?php namespace Clayton\Formify\Interfaces;

interface FormProcessorInterface {
		
	public function validate($input, $form);
	
	public function failed($input, $form, $object = NULL);
	
	public function finish($input, $form, $object = NULL);
		
}