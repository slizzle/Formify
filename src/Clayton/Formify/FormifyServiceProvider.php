<?php namespace Clayton\Formify;

use Illuminate\Support\ServiceProvider;

class FormifyServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('clayton/formify');
		
		$this->app['clayton.formify']->start();

		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
/*
		$loader->alias('Field', 'App\Modules\Form\Models\Field');
		$loader->alias('Formify', 'App\Modules\Form\Facades\Formify');
*/
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
		\App::bind('clayton.field', function()
		{
			return new Models\Field;
		});

		\App::singleton('clayton.formify', function()
		{
			return new FormManager;	
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}