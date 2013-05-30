# Running Migrations

The reference documentation for running migrations can be viewed in the usual way:

	php index.php migrations:run --help

## Find out where you are

At any time, the current status of completed and pending migrations can be found with:

	php index.php migrations:status

There are no options for `status` (other than `--help`).

## Go up

To migrate your schema to the latest possible version run the command:

	php index.php migrations:run --up

This will run all migrations that haven't yet been applied.

## Go down

To reverse all migrations (i.e. to delete all the tables in your schema) run this command:

	php index.php migrations:run --down

Note that this will actually only go down as far as the *lowest migration*
[configuration value](../kohana/files/config) (defined by `minion/migration.lowest_migration`).

## Go over there

If you want to a specific version of your schema then you can use the `--to` switch,
which accepts either a migration's timestamp or a relative pointer to a migration.
This must be used with the `--group` switch.

	# Migrate the schema 5 versions down
	--to=-5
	
	# Migrate the schema 10 versions up:
	--to=+10
	
	# Migrate to a specific version
	--to=201102190811

## Look before you leap

If you want to see what a Migration is going to do then you can use the `--dry-run` switch
and the SQL will be printed to the console instead of being executed.
