#!/usr/bin/env php
<?php

use Sharky\Joomla\PluginBuildScript\Script;

require __DIR__ . '/vendor/autoload.php';

(
	new Script(
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
		$argv[1] ?? null,
	)
)->build();
