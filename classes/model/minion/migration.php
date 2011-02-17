<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for managing migrations
 */
class Model_Minion_Migration extends Model
{
	/**
	 * Database connection to use
	 * @var Kohana_Database
	 */
	protected $_db = NULL;

	/**
	 * The table that's used to store the migrations
	 * @var string
	 */
	protected $_table = 'minion_migrations';

	/**
	 * Constructs the model, taking a Database connection as the first and only 
	 * parameter
	 *
	 * @param Kohana_Database Database connection to use
	 */
	public function __construct(Kohana_Database $db)
	{
		$this->_db = $db;
	}

	/**
	 * Returns a list of migrations that are available in the filesystem
	 *
	 * @return array
	 */
	public function available_migrations()
	{
		$files = Kohana::list_files('migrations');

		return Minion_Migration_Util::compile_migrations_from_files($files);
	}

	/**
	 * Checks to see if the minion migrations table exists and attempts to 
	 * create it if it doesn't
	 *
	 * @return boolean
	 */
	public function ensure_table_exists()
	{
		$query = $this->_db->query(Database::SELECT, "SHOW TABLES like '".$this->_table."'");

		if ( ! count($query))
		{
			$sql = file_get_contents(Kohana::find_file('', 'minion_schema', 'sql'));

			$this->_db->query(NULL, $sql);
		}
	}

	/**
	 * Gets the status of all groups, whether they're in the db or not.
	 *
	 * @return array
	 */
	public function get_group_statuses()
	{
		// Start out using all the installed groups
		$groups = $this->fetch_current_versions('group', 'id');
		$available = $this->available_migrations();

		foreach ($available as $migration)
		{
			if (array_key_exists($migration['id'], $groups))
			{
				continue;
			}

			$groups[$migration['group']] = NULL;
		}

		return $groups;
	}

	/**
	 * Get or Set the table to use to store migrations
	 *
	 * Should only really be used during testing
	 *
	 * @param string Table name
	 * @return string|Model_Minion_Migration Get table name or return $this on set
	 */
	public function table($table = NULL)
	{
		if ($table === NULL)
			return $this->_table;

		$this->_table = $table;

		return $this;
	}

	/**
	 * Creates a new select query which includes all fields in the migrations 
	 * table plus a `id` field which is a combination of the timestamp and the 
	 * description
	 *
	 * @return Database_Query_Builder_Select
	 */
	protected function _select()
	{
		return DB::select('*', DB::expr('CONCAT(`group`, ":", CAST(`timestamp` AS CHAR)) AS `id`'))->from($this->_table);
	}

	/**
	 * Inserts a migration into the database
	 *
	 * @param array Migration data
	 * @return Model_Minion_Migration $this
	 */
	public function add_migration(array $migration)
	{
		DB::insert($this->_table, array('timestamp', 'group', 'description'))
			->values(array($migration['timestamp'], $migration['group'], $migration['description']))
			->execute($this->_db);

		return $this;
	}

	/**
	 * Get a migration by its id
	 *
	 * @param  string Migration ID
	 * @return array  Migration info
	 */
	public function get_migration($group, $timestamp = NULL)
	{
		if ($timestamp === NULL)
		{
			if (empty($group) OR strpos(':', $group) === FALSE)
			{
				throw new Kohana_Exception('Invalid migration id :id', array(':id' => $group));
			}

			list($group, $timestamp) = explode(':', $group);
		}

		return $this->_select()
			->where('timestamp', '=', (string) $timestamp)
			->where('group',  '=', (string) $group)
			->execute($this->_db)
			->current();
	}

	/**
	 * Deletes a migration from the database
	 *
	 * @param string|array Migration id / info
	 * @return Model_Minion_Migration $this
	 */
	public function delete_migration($migration)
	{
		if (is_array($migration))
		{
			$timestamp = $migration['timestamp'];
			$group  = $migration['group'];
		}
		else
		{
			list($timestamp, $group) = explode(':', $migration);
		}

		DB::delete($this->_table)
			->where('timestamp', '=', $timestamp)
			->where('group',  '=', $group)
			->execute($this->_db);

		return $this;
	}

	/**
	 * Update an existing migration record to reflect a new one
	 *
	 * @param array The current migration
	 * @param array The new migration
	 * @return Model_Minion_Migration $this
	 */
	public function update_migration(array $current, array $new)
	{
		$set = array();
		
		foreach ($new as $key => $value)
		{
			if ($key !== 'id' AND $current[$key] !== $value)
			{
				$set[$key] = $value;
			}
		}

		if (count($set))
		{
			DB::update($this->_table)
				->set($set)
				->where('timestamp', '=', $current['timestamp'])
				->where('group', '=', $current['group'])
				->execute($this->_db);
		}

		return $this;
	}

	/**
	 * Change the applied status for a migration
	 *
	 * @param  array Migration information
	 * @param  bool  Whether this migration has been applied or unapplied
	 * @return Model_Minion_Migration
	 */
	public function mark_migration(array $migration, $applied)
	{
		DB::update($this->_table)
			->set(array('applied' => (int) $applied))
			->where('timestamp', '=', $migration['timestamp'])
			->where('group',  '=', $migration['group'])
			->execute($this->_db);

		return $this;
	}

	/**
	 * Selects all migrations from the migratinos table
	 *
	 * @return Kohana_Database_Result
	 */
	public function fetch_all($key = NULL, $value = NULL)
	{
		return $this->_select()
			->execute($this->_db)
			->as_array($key, $value);
	}

	/**
	 * Fetches the latest version for all installed groups
	 *
	 * If a group does not have any applied migrations then no result will be 
	 * returned for it
	 *
	 * @return Kohana_Database_Result
	 */
	public function fetch_current_versions($key = 'group', $value = NULL)
	{
		// Little hack needed to do an order by before a group by
		return DB::select()
			->from(array(
				$this->_select()
				->where('applied', '>', 0)
				->order_by('timestamp', 'DESC'),
				'temp_table'
			))
			->group_by('group')
			->execute($this->_db)
			->as_array($key, $value);
	}

	/**
	 * Fetches a list of groups
	 *
	 * @return array 
	 */
	public function fetch_groups($group_as_key = FALSE)
	{
		return DB::select()
			->from($this->_table)
			->group_by('group')
			->execute($this->_db)
			->as_array($group_as_key ? 'group' : NULL, 'group');
	}

	/**
	 * Fetch a list of migrations that need to be applied in order to reach the 
	 * required version
	 *
	 * @param string  The groups to get migrations for
	 * @param mixed   Target version
	 */
	public function fetch_required_migrations($group = NULL, $target = TRUE)
	{
		$migrations = array();

		$query = $this->_select();

		if (is_bool($target))
		{
			// If we want to limit this migration to certain groups
			if ($group !== NULL)
			{
				if (is_array($group))
				{
					$query->where('group', 'IN', $group);
				}
				else
				{
					$query->where('group', '=', $group);
				}
			}

			// If we're migrating up
			if ($target === TRUE)
			{
				$query
					->where('applied', '=', 0)
					->order_by('timestamp', 'ASC');
			}
			// If we're migrating down
			else
			{
				$query
					->where('applied', '>', 0)
					->order_by('timestamp', 'DESC');
			}
		}
		else
		{
			list($target, $up) = $this->resolve_target($group, $target);

			$query->where('group', '=', $group);

			if ($up)
			{
				$query
					->where('timestamp', '<=', $target)
					->where('applied', '=', 0)
					->order_by('timestamp', 'ASC');
			}
			else
			{
				$query
					->where('timestamp', '>', $target)
					->order_by('timestamp', 'DESC');
			}
		}

		return $query->execute($this->_db)->as_array();
	}
}
