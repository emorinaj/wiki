<?php
/**
 * EGroupware Wiki
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 */

require('parse/macros.php');
require('parse/html.php');

// Execute a macro directly from the URL.
function action_macro()
{
	global $ViewMacroEngine, $macro, $parms;

	if(!empty($ViewMacroEngine[$macro]))
	{
		print $ViewMacroEngine[$macro]($parms);
	}
}
