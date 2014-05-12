<?php namespace Clayton\Formify;

use \Illuminate\Support\Facades\App;
use \Illuminate\Support\Facades\Input;
use \Illuminate\Support\Facades\Session;
use \Illuminate\Support\Facades\Request;
use \Illuminate\Support\Facades\Redirect;
use \Illuminate\Support\Facades\Event;

use \App\Modules\Messages\Facades\Messages;

use \Clayton\Formify\Models\Field;
use \Clayton\Formify\Models\ValidationException;

use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;



define('FORM_POST_KEY', 'form-post-id'); 

class FormManager {

	public $forms = [];
	
	public function __construct()
	{
		$this->start();
	}
	
	public function start()
	{
		App::error(function(NotFoundHttpException $exception, $code)
		{
			if(Request::getMethod() === "POST" && Input::has(FORM_POST_KEY))
			{
				return $this->process();
			}
		});		
			
	}
	
	public function process()
	{
		$postId = Input::get(FORM_POST_KEY);
		$forms = Session::get('forms');
		
		$formClass = $forms[$postId]['form'];
		
		$form = new $formClass();
		$form->build();
		
		$model = NULL;
		if(isset($forms[$postId]['model']))
		{
			$class = $forms[$postId]['model']['class'];
			$id = $forms[$postId]['model']['id'];
			$model = $class::findOrFail($id);
		}
		
		$processorClasses = $forms[$postId]['processors'];
		
		$input = Input::except(FORM_POST_KEY, '_token');

		foreach($processorClasses as $class)
		{
			$processor = new $class();
			try
			{	
				$processor->validate($input, $form);
			}
			catch(ValidationException $errors)
			{
				Messages::error($errors->get()->all());
				$processor->failed($input, $form, $model);
				return Redirect::back()->withInput($input);
			}
			
			if($response = $processor->finish($input, $form, $model))
			{			
				return $response;
			}
		}
				
		return Redirect::back();

	}
	
	public function make($formClass, $formProcessorClass = NULL)
	{
		$postId = str_random(40);
		$form = new $formClass();
		$form->build();
		$form[FORM_POST_KEY] = Field::hidden(FORM_POST_KEY)->value($postId)->onClose(TRUE);
		
		$formProcessors = [];
		if(!is_null($formProcessorClass) && class_exists($formProcessorClass))
		{
			$formProcessors[] = $formProcessorClass;
		}
		elseif(is_null($formProcessorClass) && class_exists($formClass."Processor"))
		{
			$formProcessors[] = $formClass."Processor";
		}
		$formProcessors = array_merge($formProcessors, Event::fire('form.processors.attach', [$form]));
		
		$this->forms[$postId] = [
		   'form' => $formClass,
		   'processors' => $formProcessors,
		];
		
		Session::put('forms', $this->forms);
		return $form;	
	}
	
	public function bind(Form $form, \Eloquent $object)
	{
		$postId = $form[FORM_POST_KEY]->value;
		$this->forms[$postId]['model']['class'] = get_class($object);
		$this->forms[$postId]['model']['id'] = $object->id;
		$form->build($object);
		$form[FORM_POST_KEY] = Field::hidden(FORM_POST_KEY)->value($postId)->onClose(TRUE);
		Session::put('forms', $this->forms);
		return $form->populate($object);
	}
	
	public function options($objects, $valueKey, $titleKey)
	{
		$options = [];
		foreach($objects as $object)
		{
			if(strpos($titleKey, ':') === 0)
			{
				$function = ltrim($titleKey, ':');
				$options[$object[$valueKey]] = $object->$function();
			}
			else
			{
				$options[$object[$valueKey]] = $object[$titleKey];
			}
		}
		return $options;
	}


}