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


function get_proc_output($handle, $pipes, string &$stdout, string &$stderr): int
{
	// Reference: https://gist.github.com/Youka/f8102eacfccc35982c29
	$timeout_in_second = 60;
	$start = microtime(true);
	$status = null;
	$exitcode = null;
	while (microtime(true) - $start < $timeout_in_second) {
		$status = proc_get_status($handle);

		$stdout .= stream_get_contents($pipes[1]);
		$stderr .= stream_get_contents($pipes[2]);
		if (!$status['running']) {
			// Only first call of this function return real value, next calls return -1.
			// So I have to capture it immediately.
			$exitcode = $status['exitcode'];
			break;
		}

		usleep(1000);
	}

	if (is_null($status) == false && $status['running']) {
		assert(is_null($exitcode));
		proc_terminate($handle);
	}
	$stdout .= stream_get_contents($pipes[1]);
	$stderr .= stream_get_contents($pipes[2]);
	proc_close($handle);
	if (is_null($exitcode))
		return -1;
	else
		return $exitcode;
}