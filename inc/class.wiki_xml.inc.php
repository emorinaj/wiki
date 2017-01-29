<?php
/**
 * EGroupware Wiki - XML Import & Export
 *
 * @link http://www.egroupware.org
 * @package wiki
 * @author Ralf Becker <RalfBecker-AT-outdoor-training.de>
 * @copyright (C) 2004-17 by RalfBecker-AT-outdoor-training.de
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @version $Id$
 */

use EGroupware\Api;
use EGroupware\Api\Framework;

include_once(EGW_INCLUDE_ROOT.'/etemplate/inc/class.xmltool.inc.php');

/**
 * XML Import & Export
 */
class wiki_xml extends wiki_bo
{
	var $public_functions = array(
		'export' => True,
	);

	function __construct($wiki_id=0)
	{
		parent::__construct($wiki_id);
	}

	function export($name='',$lang='',$modified=0)
	{
		if (!$name) $name = $_GET['page'];
		if (!$lang) $lang = $_GET['lang'];
		if (!is_array($lang))
		{
			$lang = $lang ? explode(',',$lang) : False;
		}
		if (!$modified) $modified = (int) $_GET['modified'];

		header('Content-Type: text/xml; charset=utf-8');

		$xml_doc = new xmldoc();
		$xml_doc->add_comment('$'.'Id$');	// to be able to comit the file
		$xml_doc->add_comment("eGroupWare wiki-pages matching '$name%'".
			($lang ? " and lang in(".implode(',',$lang).')':'').
			($modified ? " modified since ".date('Y-m-d H:m:i',$modified):'').
			", exported ".date('Y-m-d H:m:i',$exported=time())." from $_SERVER[HTTP_HOST]");

		$xml_wiki = new xmlnode('wiki');

		foreach($this->find($name.'%','wiki_name') as $page)
		{
			if ($lang && !in_array($page['lang'],$lang))
			{
				//echo "continue as page[lang]=$page[lang] not in ".print_r($lang,True)."<br>\n";
				continue;	// wrong language or not modified since $modified
			}
			$page_obj = $this->page($page);	// read the complete page
			$page_obj->read();
			$page = $page_obj->as_array();
			unset($page['wiki_id']);		// we dont export the wiki-id

			if ($modified && $modified > $page['time'])
			{
				//echo "continue as page[time]=$page[time] < $modified<br>\n";
				continue;	// not modified since $modified
			}

			$xml_page = new xmlnode('page');
			foreach(Api\Translation::convert($page, Api\Translation::charset(), 'utf-8') as $attr => $val)
			{
				if ($attr != 'text')
				{
					$xml_page->set_attribute($attr,$val);
				}
				else
				{
					$xml_page->set_value($val);
				}
			}
			$xml_wiki->add_node($xml_page);
		}
		$xml_wiki->set_attribute('exported',$exported);
		if ($lang)
		{
			$xml_wiki->set_attribute('languages',implode(',',$lang));
		}
		if ($name)
		{
			$xml_wiki->set_attribute('matching',$name.'%');
		}
		if ($modified)
		{
			$xml_wiki->set_attribute('modified',$modified);
		}
		$xml_doc->add_root($xml_wiki);
		$xml = $xml_doc->export_xml();

		//if ($this->debug)
		{
			//echo "<pre>\n" . htmlentities($xml) . "\n</pre>\n";
			echo $xml;
			exit();
		}
		return $xml;
	}

	function import($url,$debug_messages=False)
	{
		if (substr($url,0,4) == 'http')
		{
			// use proxy-config from admin >> configuration
			$xmldata = file_get_contents($url, 0, Framework::proxy_context());
		}
		else
		{
			$xmldata = file_get_contents($url);
		}
		//echo '<pre>'.htmlspecialchars($xmldata)."</pre>\n";
		if (!$xmldata)
		{
			return False;
		}
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE,   0);	// need to be off, else it eats our newlines
		$vals = null;
		xml_parse_into_struct($parser, $xmldata, $vals);
		xml_parser_free($parser);

		$imported = array();
		foreach($vals as $val)
		{
			if ($val['tag'] == 'wiki')	// wiki meta-data: eg. exported=Y-m-d h:m:i or export data
			{
				if ($val['type'] == 'open' || $val['type'] == 'complete')
				{
					$meta = $val['attributes'];
				}
				continue;
			}
			switch ($val['type'])
			{
				case 'open':
					$wiki_page = $val['attributes'];
					break;
				case 'complete':
					$wiki_page = $val['attributes'];
					// fall through
				case 'cdata':
					$wiki_page['text'] = trim($val['value']);
					$wiki_page = Api\Translation::convert($wiki_page,'utf-8');
					if ($this->write($wiki_page,False))
					{
						if ($debug_messages)
						{
							echo str_pad("<b>$wiki_page[name]:$wiki_page[lang]: $wiki_page[title]</b><pre>$wiki_page[text]</pre>\n",4096);
						}
						$imported[] = $wiki_page['name'].':'.$wiki_page['lang'];
					}
					break;
				case 'closed':
					break;
			}
		}
		return array(
			'meta' => $meta,
			'imported' => $imported,
		);
	}
}
