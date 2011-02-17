<?php foreach($groups as $group => $status): ?>
 * <?php echo $group ?> <?php echo ($status !== NULL ? $status : 'Not installed'); ?>

<?php endforeach; ?>
