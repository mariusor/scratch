<?php /* @var $model vscArrayModel */ ?>
<ul style="list-style:none;">
<?php foreach ($model->links as $aData) {?>
	<li style="">
		<a class="icon <?php echo $aData['hassecret'] ? 'locked' : 'unlocked'; ?>" style="line-height:24px;width:80%;float:none;padding-left:25px" href="<?php echo urldecode($aData['uri']) ?>" prop-modified="<?php echo $aData['modified'] ?>"><?php echo urldecode ($aData['uri']) ?></a>
	</li>
<?php } ?>
</ul>