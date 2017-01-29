<?php
/**
 * EGroupware Wiki - Configuration
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (C) 2004-17 by RalfBecker-AT-outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

$GLOBALS['egw_info']['server']['found_validation_hook'] = array('final_validation');

function final_validation($settings)
{
	//echo "final_validation()"; _debug_array($settings);
	if ($settings['allow_anonymous'])
	{
		// check if anon user set and exists
		if (!$settings['anonymous_username'] || !($anon_user = $GLOBALS['egw']->accounts->name2id($settings['anonymous_username'])))
		{
			$GLOBALS['config_error'] = 'Anonymous user does NOT exist!';
		}
		else	// check if anon user has run-rights for manual
		{
			$locations = $GLOBALS['egw']->acl->get_all_location_rights($anon_user,'wiki');
			if (!$locations['run'])
			{
				$GLOBALS['config_error'] = 'Anonymous user has NO run-rights for the application!';
			}
		}
	}
}
