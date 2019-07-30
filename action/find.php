<?php
/**
 * EGroupware Wiki
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

require('parse/html.php');
require(TemplateDir . '/find.php');

// Find a string in the database.
function action_find()
{
	global $pagestore;

	$find = $_POST['find'] ? $_POST['find'] : $_GET['find'];

	$list = $pagestore->find($find);

	$text = '';
	foreach($list as $page)
	{
		$text .= html_ref($page, $page) . html_newline();
	}
	template_find(array(
		'find'  => $find,
		'pages' => $text)
	);
}
