<?php /*@var $this vscHtml5View */ ?>
<section title="<?php echo $model['help'] ?>" <?php if (!is_null ($model['modified'])) echo 'data-modified="' . strtotime($model['modified']) . '"';?>><?php echo $model['content'];?></section>

