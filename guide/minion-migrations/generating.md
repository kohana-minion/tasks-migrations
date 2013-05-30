# New Migrations

Every set of modifications to the database schema should be done through a new Migration.

The task for this is `migrations:new`, and its full documentation is available with the usual help command:

	php index.php migrations:new --help

The `migrations:new` task takes three parameters: *group*, *location*, and *description*.
A new file will be created, containing the skeleton of a child class of [Minion_Migration_Base]
that must be fleshed out with code to perform the migration (and its reversal).

## Group

Groups provide a means to run particular sets of Migrations separately.
Every Migration must be in a group.

For modules' Migrations, the group name is usually the same as the module name (or at least prefixed with it).

## Description

The description is optional, and if provided will be turned into a normalised suffix
for the Migration class's file (e.g. `20130529140938_initial-installation.php`)
and also shown in the output of the `status` and `run` tasks.
