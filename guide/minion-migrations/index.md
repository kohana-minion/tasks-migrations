# Minion Migrations

*Migrations? But I'm not leaving the country!*

The term "migration" is used to describe the movement from one location to another.
In the context of databases it refers to managing differences between schemas.
In essence, it's version control for databases, and this module makes it easy to roll back to earlier versions at any time.

Every set of changes to the database schema is carried out from a Migration,
which is just a class that extends [Minion_Migration_Base].
These Migration classes each contain an `up()` and `down()` method,
which do the work of applying changes to the database schema and undoing these changes, respectively.

Once a Migration has been released (i.e. has possibly been run on a production system) it should not be modified.
Instead, new Migrations must be created.

## Metadata table

The first time a Migration is run, a special table (by default called `minion_migrations`) is created
in which to store information about which Migrations have been applied.

The table name can be customised with the `minion/migration.table` [configuration value](../kohana/files/config).

The table definition is as follows:

	CREATE TABLE `<table_name>` (
		`timestamp` varchar(14) NOT NULL,
		`description` varchar(100) NOT NULL,
		`group` varchar(100) NOT NULL,
		`applied` tinyint(1) DEFAULT '0',
		PRIMARY KEY (`timestamp`,`group`),
		UNIQUE KEY `MIGRATION_ID` (`timestamp`,`description`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;
