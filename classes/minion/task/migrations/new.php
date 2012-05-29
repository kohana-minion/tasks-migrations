<?php defined('SYSPATH') or die('No direct script access.');

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
class Minion_Task_Migrations_New extends Kohana_Minion_Task_Migrations_New { }
