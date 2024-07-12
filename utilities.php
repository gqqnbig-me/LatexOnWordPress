<?php

namespace gqqnbig;

/**
 * Detect the full path of an executable
 * @param $executable_name string the short name of an executable
 * @return string|null If the full path can't be found, return null.
 */
function get_executable_path(string $executable_name): ?string
{
	$path = null;
	if (PHP_OS_FAMILY === 'Windows') {
		$path = trim(shell_exec('where ' . escapeshellarg($executable_name)));
		if (strlen($path) === 0 || file_exists($path) === false)
			$path = null;
	} else {
		$path = trim(shell_exec('type ' . escapeshellarg($executable_name)));
		if (preg_match('/' . preg_quote($executable_name) . ' is (.+)$/', $path, $matches) === 1 && count($matches) > 0 && file_exists($matches[1]))
			$path = $matches[1];
		else
			$path = null;
	}

	return $path;
}