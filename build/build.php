<?php

require (dirname(__DIR__)) . '/build-script/script.php';

(
	new PluginBuildScript(
		str_replace('\\', '/', dirname(__DIR__)),
		str_replace('\\', '/', __DIR__),
		'removegenerator',
		'system',
		'plg_system_removegenerator',
		'SharkyKZ',
		'System - Remove Generator',
		'Plugin for removing generator tag.',
		'(5\.|4\.|3\.([89]|10))',
		'5.4',
	)
)->build();
