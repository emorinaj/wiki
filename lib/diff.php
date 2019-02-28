<?php
// $Id$

require_once(__DIR__ . '/../../vendor/autoload.php');

// Compute the difference between two sets of text.
function diff_compute($text1, $text2)
{
	$diff = new \Horde_Text_Diff('auto', array(explode("\n",$text1), explode("\n",$text2)));
	$renderer = new \Horde_Text_Diff_Renderer_Unified();
	return $renderer->render($diff);
}

// Parse diff output into nice HTML.
function diff_parse($text)
{
	global $DiffEngine;

	return parseText($text, $DiffEngine, '');
}
