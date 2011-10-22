<?php /*@var $this vscHtml5View */ ?>
<div title="<?php echo $model['help'] ?>" id="content" <?php if (!is_null ($model['creation'])) echo 'modified="' . strtotime($model['creation']) . '"';?>><?php echo str_replace('<!--{RANDURL}-->', $model['rand_uri'], $model['data']);?></div>

