<?php defined('SYSPATH') or die('No direct script access.');

/**
 * The Run task compares the current version of the database with the target
 * version and then executes the necessary commands to bring the database up to
 * date
 *
 * Available config options are:
 *
 * --down
 *
 *   Migrate the group(s) down
 *
 * --up
 *
 *   Migrate the group(s) up
 *
 * --to=(timestamp|+up_migrations|down_migrations)
 *
 *   Migrate to a specific timestamp, or up $up_migrations, or down $down_migrations
 *
 *   Cannot be used with --groups, must be used with --group
 *
 * --group=group
 *
 *   Specify a single group to perform migrations on
 *
 * --groups=group[,group2[,group3...]]
 *
 *   A list of groups that will be used to source migration files.  By default
 *   migrations will be loaded from all available groups.
 *
 *   Note, only --up and --down can be used with --groups
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
class Minion_Task_Migrations_Run extends Kohana_Minion_Task_Migrations_Run { }
