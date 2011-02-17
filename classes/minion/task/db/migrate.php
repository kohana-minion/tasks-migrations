<?php defined('SYSPATH') or die('No direct script access.');

/**
 * The Migrate task compares the current version of the database with the target 
 * version and then executes the necessary commands to bring the database up to 
 * date
 *
 * Available config options are:
 *
 * --versions=[group:]version
 *
 *  Used to specify the version to migrate the database to.  The group prefix 
 *  is used to specify the target version of an individual group. Version
 *  specifies the target version, which can be either:
 *
 *     * A migration version (migrates up/down to that version)
 *     * TRUE (runs all migrations to get to the latest version)
 *     * FALSE (undoes all appled migrations)
 *
 *  An example of a migration version is 20101229015800
 *
 *  If you specify TRUE / FALSE without a group then the default migration 
 *  direction for groups without a specified version will be up / down respectively.
 *
 *  If you're only specifying a migration version then you *must* specify a group
 *
 * --groups=group[,group2[,group3...]]
 *
 *  A list of groups (under the migrations folder in the cascading 
 *  filesystem) that will be used to source migration files.  By default 
 *  migrations will be loaded from all available groups
 *
 * --dry-run
 *
 *  No value taken, if this is specified then instead of executing the SQL it 
 *  will be printed to the console
 *
 * --quiet
 *
 *  Suppress all unnecessary output.  If --dry-run is enabled then only dry run 
 *  SQL will be output
 *
 * @author Matt Button <matthew@sigswitch.com>
 */
class Minion_Task_Db_Migrate extends Minion_Task
{

	/*
	 * The default direction for migrations, TRUE = up, FALSE = down
	 * @var boolean
	 */
	protected $_default_direction = TRUE;

	/**
	 * A set of config options that this task accepts
	 * @var array
	 */
	protected $_config = array(
		'versions',
		'groups',
		'dry-run',
		'quiet'
	);

	/**
	 * Migrates the database to the version specified
	 *
	 * @param array Configuration to use
	 */
	public function execute(array $config)
	{
		$k_config = Kohana::config('minion/migration');

		// Grab user input, using sensible defaults
		$specified_groups = Arr::get($config, 'groups',   NULL);
		$versions            = Arr::get($config, 'versions',    NULL);
		$dry_run             = array_key_exists('dry-run', $config);
		$quiet               = array_key_exists('quiet', $config);

		$targets   = $this->_parse_target_versions($versions);
		$groups = $this->_parse_groups($specified_groups);

		$db        = Database::instance();
		$model     = new Model_Minion_Migration($db);


		$model->ensure_table_exists();

		$manager = new Minion_Migration_Manager($db, $model);

		$manager
			// Sync the available migrations with those in the db
			->sync_migration_files()

			->set_dry_run($dry_run);

		try
		{
			// Run migrations for specified groups & versions
			$manager->run_migration($groups, $targets, $this->_default_direction);
		}
		catch(Minion_Migration_Exception $e)
		{
			return View::factory('minion/task/db/migrate/exception')
				->set('migration', $e->get_migration())
				->set('error',     $e->getMessage());
		}


		$view = View::factory('minion/task/db/migrate')
			->set('dry_run', $dry_run)
			->set('quiet', $quiet)
			->set('dry_run_sql', $manager->get_dry_run_sql())
			->set('executed_migrations', $manager->get_executed_migrations())
			->set('group_versions', $model->fetch_current_versions());

		return $view;
	}

	/**
	 * Parses a comma delimited set of groups and returns an array of them
	 *
	 * @param  string Comma delimited string of groups
	 * @return array  Locations
	 */
	protected function _parse_groups($group)
	{
		if (is_array($group))
			return $group;

		$group = trim($group);

		if (empty($group))
			return array();

		$groups = array();
		$group  = explode(',', trim($group, ','));

		if ( ! empty($group))
		{
			foreach ($group as $a_group)
			{
				$groups[] = trim($a_group, '/');
			}
		}

		return $groups;
	}

	/**
	 * Parses a set of target versions from user input
	 *
	 * Valid input formats for targets are:
	 *
	 *    TRUE
	 *
	 *    FALSE
	 *
	 *    {group}:(TRUE|FALSE|{migration_id})
	 *
	 * @param  string Target version(s) specified by user
	 * @return array  Versions
	 */
	protected function _parse_target_versions($versions)
	{
		if (empty($versions))
			return array();

		$targets = array();

		if ( ! is_array($versions))
		{
			$versions = explode(',', trim($versions));
		}

		foreach ($versions as $version)
		{
			$target = $this->_parse_version($version);

			if (is_array($target))
			{
				list($group, $version) = $target;

				$targets[$group] = $version;
			}
			else
			{
				$this->_default_direction = $target;
			}
		}

		return $targets;
	}

	/*
	 * Helper function for parsing target versions in user input
	 *
	 * @param  string         Input migration target
	 * @return boolean|string The parsed target
	 */
	protected function _parse_version($version)
	{
		if (is_bool($version))
			return $version;

		if ($version === 'TRUE' OR $version == 'FALSE')
			return $version === 'TRUE';

		if (strpos($version, ':') !== FALSE)
			return explode(':', $version);

		throw new Kohana_Exception('Invalid target version :version', array(':version' => $version));
	}
}
