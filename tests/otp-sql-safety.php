<?php

$files = array(
	'inc/gateways/class-otpa-abstract-gateway.php',
	'inc/gateways/class-otpa-bandwidth-sms-gateway.php',
	'inc/class-otpa-logger.php',
	'inc/templates/admin/logs-settings-page.php',
);
$unsafe = array();

foreach ($files as $file) {
	$lines = file($file);
	foreach ($lines as $line_number => $line) {
		$line = str_replace(array('{$wpdb->prefix}', '{$wpdb->usermeta}', '{$phone_placeholders}'), '', $line);
		if (preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|SHOW|TRUNCATE|WHERE|IN)\b.*(?:\{\$|\$[a-zA-Z_][a-zA-Z0-9_]*\s*\.)/i', $line)) {
			$unsafe[] = $file . ':' . ($line_number + 1);
		}
	}
}

if (!empty($unsafe)) {
	fwrite(STDERR, "Unsafe SQL interpolation:\n" . implode("\n", $unsafe) . "\n");
	exit(1);
}
