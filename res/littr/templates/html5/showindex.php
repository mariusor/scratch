<?php /* @var $model vscArrayModel */ ?>
<ul style="list-style:none;">
<?php
if (count ($model->links) > 0 ) {
	foreach ($model->links as $key => $aData) {
?>
	<li style="" title="Size: <?php echo $aData['size'];?> chars; Last modified: <?php echo $this->dateFormat('%fancy', $aData['modified']); ?>" >
		<a class="icon <?php echo $aData['hassecret'] ? 'locked' : 'unlocked'; ?>" href="<?php echo urldecode($aData['uri']) ?>" data-index="<?php echo $key?>" data-modified="<?php echo $aData['modified'] ?>" style="padding: 7px 40px;"><?php echo urldecode ($aData['uri']) ?></a>
	</li>
<?php
	}
} else {
?>
	<li>
		<em>Nothing to see here please <a href="?random">move along</a>.</em>
	</li>
<?php
}
?>
</ul>
