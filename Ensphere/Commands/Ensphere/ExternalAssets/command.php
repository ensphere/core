<?php namespace EnsphereCore\Commands\Ensphere\ExternalAssets;

use Illuminate\Console\Command as IlluminateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Command extends IlluminateCommand {

	/**
	 * [$name description]
	 * @var string
	 */
	protected $name = 'ensphere:external-assets';

	/**
	 * [$description description]
	 * @var string
	 */
	protected $description = 'Retrieves external assets and stores localy';

	/**
	 * [__construct description]
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * [fire description]
	 * @return [type] [description]
	 */
	public function fire()
	{
		$this->info('running...');
	}

}