<?php defined('SYSPATH') or die('No direct script access.');

class Kohana_Minion_Task_Migrations_Status extends Minion_Task {

	/**
	 * Execute the task
	 *
	 * @param array Config for the task
	 */
	public function execute(array $config)
	{
		$db        = Database::instance();
		$model     = new Model_Minion_Migration($db);

		$view = new View('minion/task/migrations/status');

		$view->groups = $model->get_group_statuses();

		echo $view;
	}
}
