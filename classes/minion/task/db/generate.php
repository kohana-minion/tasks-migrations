<?php defined('SYSPATH') or die('No direct script access.');

/**
 * The generate task provides an easy way to create migration files
 *
 * Available config options are:
 *
 * --group=path/to/migration/group
 *
 *  This is a required config option, use it specify in which group the
 *  migration should be stored.  Due to the nature of the cascading filesystem
 *  minion doesn't automatically know where a migration is stored so make sure
 *  you pass in the full path to your migrations folder, e.g.
 *
 *  # The group of the migrations folder is modules/myapp/migrations/myapp/
 *  --group=modules/myapp/migrations/myapp/
 *
 *  On nix based systems you should be able to tab complete the path
 *
 * --description="Description of migration here"
 *
 *  This is an arbitrary description of the migration, used to build the
 *  filename.  It is required but can be changed manually later on without
 *  affecting the integrity of the migration.
 *
 *  The description will be
 *
 * @author Matt Button <matthew@sigswitch.com>
 */
class Minion_Task_Db_Generate extends Minion_Task
{
	/**
	 * A set of config options that this task accepts
	 * @var array
	 */
	protected $_config = array(
		'group',
		'description',
		'location'
	);

	/**
	 * Execute the task
	 *
	 * @param array Configuration
	 */
	public function execute(array $config)
	{
		try
		{
			$file = $this->generate($config);
			Minion_CLI::write('Migration generated: '.$file);	
		}
		catch(ErrorException $e)
		{
			Minion_CLI::write($e->getMessage());
		}

	}
	
	public function generate($config)
	{
		$defaults = array(
			'location'    => APPPATH,
			'description' => '',
			'group'       => NULL,
		);

		$config = array_merge($defaults, $config);

		// Trim slashes in group
		$config['group'] = trim($config['group'], '/');

		if ( ! $this->_valid_group($config['group']))
		{
			throw new ErrorException("Please provide a valid --group\nSee help for more info");
		}

		$group = $config['group'].'/';
		$description = $config['description'];
		$location = rtrim(realpath($config['location']), '/').'/migrations/';

		// {year}{month}{day}{hour}{minute}{second}
		$time = date('YmdHis');
		$class = $this->_generate_classname($group, $time);
		$file = $this->_generate_filename($location, $group, $time, $description);


		$data = Kohana::FILE_SECURITY.View::factory('minion/task/db/generate/template')
			->set('class', $class)
			->set('description', $description)
			->render();

		if ( ! is_dir(dirname($file)))
		{
			mkdir(dirname($file), 0775, TRUE);
		}

		file_put_contents($file, $data);

		return $file;
	}

	/**
	 * Generate a class name from the group
	 *
	 * @param  string group
	 * @param  string Timestamp
	 * @return string Class name
	 */
	protected function _generate_classname($group, $time)
	{
		$class = ucwords(str_replace('/', ' ', $group));

		// If group is empty then we want to avoid double underscore in the
		// class name
		if ( ! empty($class))
		{
			$class .= '_';
		}

		$class .= $time;

		return 'Migration_'.preg_replace('~[^a-zA-Z0-9]+~', '_', $class);
	}

	/**
	 * Generates a filename from the group, time and description
	 *
	 * @param  string Location to store migration
	 * @param  string Timestamp
	 * @param  string Description
	 * @return string Filename
	 */
	public function _generate_filename($location, $group, $time, $description)
	{
		// Max 100 characters, lowecase filenames.
		$label = substr(strtolower($description), 0, 100);
		// Only letters
		$label = preg_replace('~[^a-z]+~', '-', $label);
		// Add the location, group, and time
		$filename = $location.$group.$time.'_'.$label;
		// If description was empty, trim underscores
		$filename = trim($filename, '_');
		return $filename.EXT;
	}

	protected function _valid_group($group)
	{
		// Group cannot be empty
		if ( ! Valid::not_empty($group))
			return FALSE;

		// Can only consist of alpha-numeric values, dashes, underscores, and slashes
		if (preg_match('/[^a-zA-Z0-9\/_-]/', $group))
			return FALSE;

		// Must also contain at least one alpha-numeric value
		if ( ! preg_match('/[a-zA-Z0-9]/', $group))
			return FALSE; // --group="/" breaks things but "a/b" should be allowed

		return TRUE;
	}

}
