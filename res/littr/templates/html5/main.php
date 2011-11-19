<?php /*@var $this vscHtml5View */ ?>
<div title="<?php echo $model['help'] ?>" id="content" <?php if (!is_null ($model['modified'])) echo 'data-modified="' . strtotime($model['modified']) . '"';?>><?php echo $model['content'];?></div>

