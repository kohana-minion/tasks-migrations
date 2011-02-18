<?php foreach($groups as $group => $status): ?>
 * <?php echo $group ?> <?php echo ($status !== NULL ? $status['timestamp'].' ('.$status['description'].')' : 'Not installed'); ?>

<?php endforeach; ?>
