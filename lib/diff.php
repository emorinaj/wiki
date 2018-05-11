<?php
// $Id$

// Compute the difference between two sets of text.
function diff_compute($text1, $text2)
{
	global $TempDir, $DiffCmd, $ErrorCreatingTemp, $ErrorWritingTemp;

	$num = trim($GLOBALS['egw_info']['user']['account_id']).'_'.randomstring(8); //strncmp(PHP_OS,'WIN',3) ? posix_getpid() : rand();
	//error_log(__METHOD__.__LINE__.' RandomString:'.$num);
	$temp1 = $TempDir . '/wiki_' . $num . '_1.txt';
	$temp2 = $TempDir . '/wiki_' . $num . '_2.txt';

	if(!($h1 = fopen($temp1, 'w')) || !($h2 = fopen($temp2, 'w')))
		{ die($ErrorCreatingTemp); }

	if(fwrite($h1, $text1) < 0 || fwrite($h2, $text2) < 0)
		{ die($ErrorWritingTemp); }

	fclose($h1);
	fclose($h2);

	$diff = `$DiffCmd $temp1 $temp2`;

	unlink($temp1);
	unlink($temp2);

	return $diff;
}

// Parse diff output into nice HTML.
function diff_parse($text)
{
	global $DiffEngine;

	return parseText($text, $DiffEngine, '');
}

/**
 * return a random string of letters [0-9a-zA-Z] of size $size
 * Copied from Api\Auth
 * @param $size int-size of random string to return
 */
function randomstring($size)
{
	$random_char = array(
		'0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f',
		'g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v',
		'w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L',
		'M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'
	);

	// use cryptographically secure random_int available in PHP 7+
	$func = function_exists('random_int') ? 'random_int' : 'mt_rand';

	$s = '';
	for ($i=0; $i < $size; $i++)
	{
		$s .= $random_char[$func(0, count($random_char)-1)];
	}
	return $s;
}
?>
