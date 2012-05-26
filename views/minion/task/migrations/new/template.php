
<?php if(!empty($description)): ?>
/**
 * <?php echo $description.PHP_EOL; ?>
 */
<?php endif; ?>
class <?php echo $class; ?> extends Minion_Migration_Base {

	/**
	 * Run queries needed to apply this migration
	 *
	 * @param Kohana_Database $db Database connection
	 */
	public function up(Kohana_Database $db)
	{
<?php if(!empty($up)): ?>
<?php echo $up; ?>

<?php else: ?>
		// $db->query(NULL, 'CREATE TABLE ... ');
<?php endif; ?>
	}

	/**
	 * Run queries needed to remove this migration
	 *
	 * @param Kohana_Database $db Database connection
	 */
	public function down(Kohana_Database $db)
	{
<?php if(!empty($down)): ?>
<?php echo $down; ?>

<?php else: ?>
		// $db->query(NULL, 'DROP TABLE ... ');
<?php endif; ?>
	}
}
