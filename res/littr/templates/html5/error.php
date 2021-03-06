<?php
/* @var \vsc\domain\models\ErrorModel $model */
use vsc\infrastructure\vsc;
use vsc\presentation\responses\HttpResponseType;
?>
<h2><?php echo HttpResponseType::getStatus($model->getException()->getCode()); ?></h2>
<p><?php echo $model->getMessage();?></p>
<?php if (vsc::getEnv()->isDevelopment()) { ?>
<pre id="trace" style="position: fixed; bottom: 2em; display: block; font-size: 0.8em; display:none"><?php echo $model->getException()->getTraceAsString(); ?></pre>
<ul style="padding:0; font-size:0.8em"><li style="padding:0.2em;display:inline"><a href="#" onclick="p = document.getElementById('trace'); if (p.style.display=='block') p.style.display='none';else p.style.display='block'; return false">toggle trace</a></li><li style="padding:0.2em;display:inline"><a href="#" onclick="p = document.getElementById('trace'); document.location.href ='mailto:marius@habarnam.ro?subject=Problems&amp;body=' + p.innerHTML; return false;">mail me</a></li></ul>
<?php } ?>
<address style="position:fixed;bottom:0;">&copy; habarnam</address>
