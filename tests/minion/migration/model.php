<?php

/**
 * Test for the migration model
 *
 * @group minion
 * @group minion.tasks
 * @group minion.tasks.migrations
 */
class Minion_Migration_ModelTest extends Kohana_Unittest_Database_TestCase
{
	/**
	 * Runs before the test class as a whole is ran
	 * Creates the test table
	 */
	public static function setUpBeforeClass()
	{
		$sql = file_get_contents(Kohana::find_file('', 'minion_schema', 'sql'));

		$sql = str_replace('`minion_migrations`', '`test_minion_migrations`', $sql);

		Database::instance()->query(NULL, 'DROP TABLE IF EXISTS `test_minion_migrations`');
		Database::instance()->query(NULL, $sql);
	}

	/**
	 * Removes the test tables after the tests have finished
	 */
	public static function tearDownAfterClass()
	{
		Database::instance()->query(NULL, 'DROP TABLE `test_minion_migrations`');
	}

	/**
	 * Gets the dataset that should be used to populate db
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet()
	{
		return $this->createFlatXMLDataSet(
			Kohana::find_file('tests/datasets', 'minion/migration/model', 'xml')
		);
	}

	/**
	 * Get an instance of the migration model, pre-loaded with a connection to 
	 * the test database
	 *
	 * @return Model_Minion_Migration
	 */
	public function getModel()
	{
		$model = new Model_Minion_Migration($this->getKohanaConnection());

		return $model->table('test_minion_migrations');
	}

	/**
	 * Tests that the model can fetch all rows from the database
	 *
	 * @test
	 * @covers Model_Minion_Migration::fetch_all
	 */
	public function test_fetch_all()
	{
		$migrations = $this->getModel()->fetch_all();

		$this->assertSame(10, count($migrations));
	}

	/**
	 * Test that the model accurately fetches the latest versions from the database
	 *
	 * @test
	 * @covers Model_Minion_Migration::fetch_current_versions
	 */
	public function test_fetch_current_versions()
	{
		$versions = $this->getModel()
			->fetch_current_versions('group', 'timestamp');

		$this->assertSame(
			array (
				'app'      => '20101216080000',
				'dblogger' => '20101225000000',
				'done'     => '20101546112100',
			),
			$versions
		);
	}

	/**
	 * Provides test data for test_fetch_required_migrations
	 *
	 * @return array
	 */
	public function provider_fetch_required_migrations()
	{
		return array(
			// Test going up in a specific group
			array(
				array(
					array(
						array(
							'timestamp' => '20101215165000',
							'description' => 'add-name-column-to-members',
							'group' => 'app',
							'applied' => '0',
							'id' => 'app:20101215165000',
						),
						array(
							'timestamp' => '20101216000000',
							'description' => 'add-index-on-name',
							'group' => 'app',
							'applied' => '0',
							'id' => 'app:20101216000000',
						),
					),
					TRUE
				),
				'app',
				TRUE,
			),
			// Testing going down with a specific group
			array(
				array(
					array(
						array(
							'timestamp' => '20101216080000',
							'description' => 'remove-password-salt-column',
							'group' => 'app',
							'applied' => '1',
							'id' => 'app:20101216080000',
						),
						array(
							'timestamp' => '20101215164400',
							'description' => 'create-tables',
							'group' => 'app',
							'applied' => '1',
							'id' => 'app:20101215164400',
						),
					),
					FALSE
				),
				'app',
				FALSE
			),
			// Testing going up across all groups
			array(
				array(
					array(
						array(
							'timestamp' => '20101215165000',
							'description' => 'add-name-column-to-members',
							'group' => 'app',
							'applied' => '0',
							'id' => 'app:20101215165000',
						),
						array(
							'timestamp' => '20101216000000',
							'description' => 'add-index-on-name',
							'group' => 'app',
							'applied' => '0',
							'id' => 'app:20101216000000',
						),
						array(
							'timestamp' => '20101226112100',
							'description' => 'add-pk',
							'group' => 'dblogger',
							'applied' => '0',
							'id' => 'dblogger:20101226112100',
						),
						array(
							'timestamp' => '20101526112100',
							'description' => 'add-col',
							'group' => 'minion',
							'applied' => '0',
							'id' => 'minion:20101526112100',
						),
					),
					TRUE
				),
				NULL,
				TRUE
			),
			// Testing going down across all groups
			array(
				array(
					array(
						array(
							'timestamp' => '20101546112100',
							'description' => 'add-bbb',
							'group' => 'done',
							'applied' => '1',
							'id' => 'done:20101546112100'
						),
						array(
							'timestamp' => '20101536112100',
							'description' => 'add-aaa',
							'group' => 'done',
							'applied' => '1',
							'id'=> 'done:20101536112100'
						),
						array(
							'timestamp' => '20101225000000',
							'description' => 'remove-unique-index',
							'group' => 'dblogger',
							'applied' => '1',
							'id' => 'dblogger:20101225000000',
						),
						array(
							'timestamp' => '20101216080000',
							'description' => 'remove-password-salt-column',
							'group' => 'app',
							'applied' => '1',
							'id' => 'app:20101216080000',
						),
						array(
							'timestamp' => '20101215164500',
							'description' => 'create-table',
							'group' => 'dblogger',
							'applied' => '1',
							'id' => 'dblogger:20101215164500',
						),
						array(
							'timestamp' => '20101215164400',
							'description' => 'create-tables',
							'group' => 'app',
							'applied' => '1',
							'id' => 'app:20101215164400',
						),
					),
					FALSE,
				),
				NULL,
				FALSE
			),
			// Test migrating down to a specific version
			array(
				array(
					array(
						array(
							'timestamp' => '20101216080000',
							'description' => 'remove-password-salt-column',
							'group' => 'app',
							'applied' => '1',
							'id' => 'app:20101216080000',
						),
					),
					FALSE
				),
				'app',
				20101215164400
			),
			// Test migrating up from nothing to everything
			array(
				array(
					array(
						array(
							'timestamp' => '20101526112100',
							'description' => 'add-col',
							'group' => 'minion',
							'applied' => '0',
							'id' => 'minion:20101526112100',
						),
					),
					TRUE
				),
				'minion',
				'+100'
			),
			array(
				array(
					array(
						array(
							'timestamp' => '20101546112100',
							'description' => 'add-bbb',
							'group' => 'done',
							'applied' => '1',
							'id' => 'done:20101546112100'
						),
					),
					FALSE
				),
				'done',
				'-1'
			)
		);
	}

	/**
	 * Test that migrations are fetched in the right order depending on what the target is
	 *
	 * @test
	 * @dataProvider provider_fetch_required_migrations
	 * @covers Model_Minion_Migration::fetch_required_migrations
	 * @param array        An expected resultset
	 * @param string|array A group, or groups to get migrations for
	 * @param bool|string  Target to migrate to
	 */
	public function test_fetch_required_migrations($expected, $group, $target)
	{
		$this->assertSame(
			$expected,
			$this->getModel()->fetch_required_migrations( (array) $group, $target)
		);
	}


	/**
	 * Provides test data for test_get_migration
	 *
	 * @return array
	 */
	public function provider_get_migration()
	{
		return array(
			array(
				array(
					'timestamp'    => '20101215164400',
					'description'  => 'create-tables',
					'group'     => 'app',
					'applied'      => '1',
					'id'           => 'app:20101215164400'
				),
				'app',
				'20101215164400',
			)
		);
	}

	/**
	 * Tests that Model_Minion_Migration::get_migration can get a migration from 
	 * the database
	 *
	 * @test
	 * @covers Model_Minion_Migration::get_migration
	 * @dataProvider provider_get_migration
	 * @param array  Expected migration
	 * @param string The migration's group
	 * @param string The migration's timestamp
	 */
	public function test_get_migration($expected, $group, $timestamp)
	{
		$this->assertSame(
			$expected,
			$this->getModel()->get_migration($group, $timestamp)
		);
	}

	/**
	 * Provides test data for test_get_migration_throws_exception_on_invalid_input
	 *
	 * @return array
	 */
	public function provider_get_migration_throws_exception_on_invalid_input()
	{
		return array(
			array(NULL,  NULL),
			array('app', NULL),
		);
	}

	/**
	 * If invalid input is passed to get_migration then it should throw an 
	 * exception
	 *
	 * @test
	 * @covers Model_Minion_Migration::get_migration
	 * @dataProvider provider_get_migration_throws_exception_on_invalid_input
	 * @expectedException Kohana_Exception
	 */
	public function test_get_migration_throws_exception_on_invalid_input($group, $timestamp)
	{
		$this->getModel()->get_migration($group, $timestamp);
	}

	/**
	 * Provides test data for test_mark_migration
	 *
	 * @return array
	 */
	public function provider_mark_migration()
	{
		return array(
			array(
				array(
					'timestamp'   => '20101215165000',
					'description' => 'add-name-column-to-members',
					'group'    => 'app',
					'applied'     => '1',
					'id'          => 'app:20101215165000',
				),
				array(
					'timestamp'   => '20101215165000',
					'group'    => 'app',
					'description' => 'add-name-column-to-members',
				),
				TRUE
			),
			array(
				array(
					'timestamp'   => '20101215165000',
					'description' => 'add-name-column-to-members',
					'group'    => 'app',
					'applied'     => '0',
					'id'          => 'app:20101215165000',
				),
				array(
					'timestamp'   => '20101215165000',
					'group'    => 'app',
					'description' => 'add-name-column-to-members',
				),
				FALSE
			),
		);
	}

	/**
	 * Tests that Model_Minion_Migration::mark_migration() changes the applied 
	 * status of a migration
	 *
	 * @test
	 * @covers Model_Minion_Migration::mark_migration
	 * @dataProvider provider_mark_migration
	 * @param array What the DB record should look like after migration is marked
	 * @param array The migration to update
	 * @param bool  Whether the migration should be applied
	 */
	public function test_mark_migration($expected, $migration, $applied)
	{
		$model = $this->getModel();

		$model->mark_migration($migration, $applied);

		$this->assertSame(
			$expected,
			$model->get_migration($migration['group'], $migration['timestamp'])
		);
	}

	/**
	 * Provides test data for test_resolve_target()
	 *
	 * @return array
	 */
	public function provider_resolve_target()
	{
		return array(
			array(
				array('20101216080000', FALSE),
				'app',
				'-1'
			),
			array(
				array(NULL, FALSE),
				'dblogger',
				'-10'
			),
			array(
				array('20101226112100', TRUE),
				'dblogger',
				'+1',
			),
			array(
				array(NULL, TRUE),
				'app',
				'+10'
			),
			array(
				array(NULL, TRUE),
				'dblogger',
				'+100'
			),
		);
	}

	/**
	 * Test that we can resolve a target version for a group.
	 *
	 * Target version can be relative (+migrations_up / -migrations_down) or absolute (i.e. timestamp)
	 *
	 * @test
	 * @dataProvider provider_resolve_target
	 * @covers Model_Minion_Migration::resolve_target
	 * @param array  Expected output
	 * @param string Group name
	 * @param string Target version
	 */
	public function test_resolve_target($expected, $group, $target)
	{
		$this->assertSame(
			$expected,
			$this->getModel()->resolve_target( (array) $group, $target)
		);
	}
}
