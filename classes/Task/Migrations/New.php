<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * The new task provides an easy way to create migration files
 *
 * Available config options are:
 *
 * --group=group_name
 *
 *  This is a required config option, use it specify in which group the
 *  migration should be stored. Migrations are stored in a `migrations`
 *  directory followed by the group name specified. By default, the `migrations`
 *  directory is created in `APPPATH` but that can be changed with `--location`
 *
 * --location=modules/auth
 *
 *  Specified the path of the migration (without the `migrations` directory).
 *  This value is defaulted to `APPPATH`
 *
 *  # The migration will be created in `modules/myapp/migrations/myapp/`
 *  --group=myapp --location=modules/myapp
 *
 * --description="Description of migration here"
 *
 *  This is an arbitrary description of the migration, used to build the
 *  filename.  It is required but can be changed manually later on without
 *  affecting the integrity of the migration.
 *
 * @author Matt Button <matthew@sigswitch.com>
 */
class Task_Migrations_New extends Minion_Task {
	/**
	 * A set of config options that this task accepts
	 * @var array
	 */
	protected $_options = array(
		'location'    => APPPATH,
		'description' => '',
		'group'       => NULL,
	);

	/**
	 * Execute the task
	 *
	 * @param array $options Configuration
	 */
	protected function _execute(array $options)
	{
		try
		{
			$file = $this->generate($options);
			Minion_CLI::write('Migration generated: '.$file);
		}
		catch (ErrorException $e)
		{
			Minion_CLI::write($e->getMessage());
		}

	}

	/**
	 * Generate the migration file and return the file path
	 *
	 * @param  array  $options The migration options
	 * @param  string $up      Contents of the up migration
	 * @param  string $down    Contents of the down migration
	 * @return string          Filename
	 */
	public function generate($options, $up = NULL, $down = NULL)
	{
		// Trim slashes in group
		$options['group'] = trim($options['group'], '/');

		if ( ! $this->_valid_group($options['group']))
		{
			throw new ErrorException("Please provide a valid --group\nSee help for more info");
		}

		$group = $options['group'].'/';
		$description = $options['description'];
		$location = rtrim(realpath($options['location']), '/').'/migrations/';

		// {year}{month}{day}{hour}{minute}{second}
		$time  = date('YmdHis');
		$class = $this->_generate_classname($group, $time);
		$file  = $this->_generate_filename($location, $group, $time, $description);


		$data = Kohana::FILE_SECURITY.PHP_EOL
		.View::factory('minion/task/migrations/new/template')
			->set('class', $class)
			->set('description', $description)
			->set('up', $up)
			->set('down', $down)
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
	 * @param  string $group The group
	 * @param  string $time  Timestamp
	 * @return string        Class name
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
	 * @param  string $location    Location to store migration
	 * @param  string $group       The group
	 * @param  string $time        Timestamp
	 * @param  string $description Description
	 * @return string              Filename
	 */
	protected function _generate_filename($location, $group, $time, $description)
	{
		// Max 100 characters, lowercase filenames.
		$label = substr(strtolower($description), 0, 100);

		// Only letters
		$label = preg_replace('~[^a-z]+~', '-', $label);

		// Add the location, group, and time
		$filename = $location.$group.$time.'_'.$label;

		// If description was empty, trim underscores
		$filename = trim($filename, '_');

		return $filename.EXT;
	}

	/**
	 * Validate that the name of the group
	 *
	 * @param  string $group The group name
	 * @return boolean
	 */
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
