<?php
/**
 * EGroupware Wiki - Hooks
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (C) 2004-17 by RalfBecker-AT-outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

use EGroupware\Api;
use EGroupware\Api\Egw;

/**
 * Static hooks for wiki
 */
class wiki_hooks
{
	/**
	 * Settings hook
	 *
	 * @param array|string $hook_data
	 */
	static public function settings($hook_data)
	{
		unset($hook_data);	// not used

		$options = array(
			// Defines not defined here
			/*WIKI_ACL_ALL*/  '_0' => lang('everyone'),
			/*WIKI_ACL_USER*/ '_1' => lang('users'),
			/*WIKI_ACL_ADMIN*/'_2' => lang('admins'),
		);
		foreach($GLOBALS['egw']->accounts->search(array('type' => 'groups')) as $acc)
		{
			$options[$acc['account_id']] = Api\Accounts::format_username($acc);
		}
		$settings = array(
			'default_read' => array(
				'type'   => 'multiselect',
				'label'  => 'Default read permission',
				'name'   => 'default_read',
				'values' => $options,
				'help'   => 'Default read permissions for creating a new page',
				'xmlrpc' => True,
				'admin'  => False,
			),
			'default_write' => array(
				'type'   => 'multiselect',
				'label'  => 'Default write permission',
				'name'   => 'default_write',
				'values' => $options,
				'help'   => 'Default write permissions for creating a new page',
				'xmlrpc' => True,
				'admin'  => False,
			)
		);

/* Needs testing
		if ($GLOBALS['egw_info']['user']['apps']['notifications'])
		{
			$details = array(
				'Title'		=>	lang('Title'),
				'Summary'	=>	lang('Summary'),
				'Category'	=>	lang('Category'),
				'Editor'	=>	lang('Person who changed the page'),
				'Content'	=>	lang('Page content'),
			);
			$settings += array(
				'notification_section' => array(
					'type'   => 'subsection',
					'title'  => 'Change Notification',
				),
				'notification_read' => array(
					'type'	=> 'check',
					'label'	=> 'Pages I have read access',
					'name'	=> 'notification_read',
					'help'	=> 'If a page I have read access to is changed, send a notification',
					'default' => 0
				),
				'notification_write' => array(
					'type'	=> 'check',
					'label'	=> 'Pages I have write access',
					'name'	=> 'notification_write',
					'help'	=> 'If a page I have write access to is changed, send a notification',
					'default' => 0
				),
				'notification_regex' => array(
					'type'	=> 'text',
					'label'	=> 'Pages that match this regular expression',
					'name'	=> 'notification_regex',
					'rows'	=> 6,
					'cols'	=> 50,
					'help'	=> 'If a page title matches this regular expression, send a notification.  You can look at title, name, lang, text using name: regex',
					'default' => ''
				),
				'notification_message' => array(
					'type'	=> 'notify',
					'label'	=> 'Message',
					'name'	=> 'notification_message',
					'help'	=> 'Message to send',
					'rows'	=> 6,
					'cols'	=> 50,
					'values'	=> $details,
					'default'	=> 'On $$Date$$ $$Editor$$ changed $$Title$$
$$Summary$$
$$Content$$'
				)
			);
		}
*/
		if ($GLOBALS['egw_info']['user']['apps']['filemanager'] || !empty($hook_data['setup']))
		{
			if ($GLOBALS['egw']->accounts->exists('Default') == 2)
			{
				$default = '/home/Default';
			}
			else
			{
				$default = $GLOBALS['egw']->accounts->id2name($GLOBALS['egw_info']['user']['account_primary_group']);
			}
			$settings['upload_dir'] = array(
				'type'  => 'vfs_dir',
				'label' => 'VFS upload directory',
				'name'  => 'upload_dir',
				'size'  => 50,
				'help'  => 'Upload directory for pasted or dragged in images in EGroupware VFS (filemanager).',
				'xmlrpc' => True,
				'admin'  => False,
				'default' => $default,
			);
		}
		return $settings;
	}

	/**
	 * Hook for admin menu
	 *
	 * @param array|string $hook_data
	 */
	public static function admin($hook_data)
	{
		unset($hook_data);	// not used, but required by function signature

		$title = $appname = 'wiki';
		$file = Array(
			'Site Configuration' => Egw::link('/index.php','menuaction=admin.admin_config.index&appname=' . $appname.'&ajax=true'),
		//	'Lock / Unlock Pages' => $GLOBALS['egw']->link('/wiki/index.php','action=admin&locking=1'),
			'Block / Unblock hosts' => Egw::link('/wiki/index.php','action=admin&blocking=1'),
			array(
				'id' => 'apps/wiki/rebuild_links',
				'text' => lang('Rebuild Links'),
				'no_lang' => true,
				'link' => "javascript:egw.message('".lang('Rebuild Links') . "<br />" .lang('Please wait...')."','info'); " .
					"egw.json('wiki.wiki_hooks.ajax_rebuildlinks').sendRequest(true);"
			)
		);
		//Do not modify below this line
		display_section($appname,$title,$file);
	}

	/**
	 * Hook for sidebox menu
	 *
	 * @param array|string $hook_data
	 */
	public static function sidebox_menu($hook_data)
	{
		unset($hook_data);	// not used, but required by function signature

		$appname = 'wiki';
		$menu_title = lang('Wiki Menu');
		$file = Array(
			'Recent Changes' => $GLOBALS['egw']->link('/wiki/index.php','page=RecentChanges'),
		);

		if ($GLOBALS['egw_info']['user']['apps']['admin'])
		{
			$menu_title = lang('Wiki Administration');
			$file = Array(
				'Site Configuration' => $GLOBALS['egw']->link('/index.php','menuaction=admin.admin_config.index&appname=' . $appname.'&ajax=true'),
			//	'Lock / Unlock Pages' => $GLOBALS['egw']->link('/wiki/index.php','action=admin&locking=1'),
				'Block / Unblock Hosts' => $GLOBALS['egw']->link('/wiki/index.php','action=admin&blocking=1')
			);
			display_sidebox($appname,$menu_title,$file);
		}
	}

	/**
	 * Hook called by link-class to include infolog in the appregistry of the linkage
	 *
	 * @param array|string $location location and other parameters (not used)
	 * @return array with method-names
	 */
	static function search_link($location)
	{
		unset($location);	// not used, but required by function signature

		return array(
			'query'      => 'wiki.wiki_bo.link_query',
			'title'      => 'wiki.wiki_bo.link_title',
			'view'       => array(
				'menuaction' => 'wiki.wiki_ui.view',
			),
			'view_id'    => 'page',
		);
	}

	static function ajax_rebuildlinks() {
		$wiki = new wiki_hooks();
		$message = $wiki->_rebuildlinks();

		Api\Json\Response::get()->apply('egw.message', array(lang('Done') . "<br />".$message,'success'));
	}

	/**
	 * rebuildlinks
	 *
	 */
	private function _rebuildlinks()
	{
		@set_time_limit(0);

		if (!$GLOBALS['egw_info']['user']['apps']['admin'])
		{
			// error_log( 'Rebuilding Links ... -> Access not allowed ');
			$GLOBALS['egw']->redirect_link('/index.php');
		}
		error_log(__METHOD__.__LINE__. ' Rebuilding EGW Link Table Entries.');
		$bo = new wiki_bo;
		global $pagestore, $page, $ParseEngine, $Entity, $ParseObject;
		if ($bo->debug) error_log(__METHOD__.__LINE__. ' Read all Artikles - ... ');
		$i=0;
		$l=0;
		foreach($bo->find(str_replace(array('*','?'),array('%','_'),'%')) as $p)
		{
			$i++;
			$Entity=array(); // this one grows like hell, and eats time as we loop, so we reset that one on each go
			$page = $p;
			if ($bo->debug) error_log(__METHOD__.__LINE__.'['.$i.']' .' Processing '.$p['name'].' - '.$p['title'].' ('.$p['lang'].') ...');
			// delete the links of the page
			if ($bo->debug) $starrt = microtime(true);
			$bo->clear_link($p);
			$start = microtime();
			$j = count($Entity);

			if ($bo->debug) $start = microtime(true);
			// do not resolve makros, as it makes no sense to store the resolved stuff with the link table
			foreach ($ParseEngine as $k => $method)
			{
				if ($method=='parse_macros' || $method=='parse_transclude' || $method=='parse_elements')
				{
					array_splice($ParseEngine,$k,1);
				}
			}
			//error_log(__METHOD__.__LINE__.' Method:'.array2string($ParseEngine));
			parseText($p['text'], $ParseEngine, $ParseObject);
			if ($bo->debug)
			{
				$end = microtime(true);
				$time= $end - $start;
				error_log(__METHOD__.__LINE__.'['.$j.']' ." Action parseText took ->$time seconds");
			}

			if ($bo->debug) $start = microtime(true);
			for(; $j < count($Entity); $j++)
			{
				if($Entity[$j][0] == 'ref')
					{$l++;$pagestore->new_link($page, $Entity[$j][1]); }
			}
			if ($bo->debug)
			{
				$end = microtime(true);
				$time= $end - $start;
				error_log(__METHOD__.__LINE__.'['.$j.']' ." Action loop and link took ->$time seconds");

				$ennd = microtime(true);
				$atime= $ennd - $starrt;
				error_log(__METHOD__.__LINE__.' ['.$i.']' ." Action for ".$p['name']." ".$p['title']." ( ".$p['lang']." ) took ->$atime seconds");
			}

			//if ($i >100) break;
		}
		$message = "$i Pages processed. $l Links inserted (or count updated).";
		return $message;
	}
}
