<?php /* @var $this vscJsonViewView */ ?>
<?php /* @var $model vscArrayModel */
	$a['status'] = $model['status'];
	if ($model['auth_token']) {
		$a['auth_token'] = $model['auth_token'];
	}
	if ($model['message']) {
		$a['message'] = $model['message'];
	}
	if ($model['modified']) {
		$a['modified'] = $model['modified'];
	}
	echo (json_encode($a));
