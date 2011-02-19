# Running Migrations

## Going up

To migrate your schema to the latest possible version run the command:

	./minion db:migrate

This command is synonomous with 

	./minion db:migrate --migrate-up

Which will run all migrations that haven't yet been applied to the schema, bringing it back in sync with the migrations in the filesystem.

## Going down

To unapply all migrations (i.e. to delete all the tables in your schema) run this command:

	./minion db:migrate --migrate-down

## Going over there

If you want to a specific version of your schema then you can use the `--migrate-to` switch, which accepts either a migration's timestamp or a relative pointer to a migration.

	// Migrate the schema 5 versions down
	--migrate-to=-5

	// Migrate the schema 10 versions up
	--migrate-to=+5

	// Migrate to a specific version
	--migrate-to=201102190811

## Look before you leap

If you want to see what SQL is going to be executed by the migrate task then simply pass the `--dry-run` switch and the SQL will be printed to the console instead of being executed.
