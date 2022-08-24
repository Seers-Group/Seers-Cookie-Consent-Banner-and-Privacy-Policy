<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.SeersCookieConsent
 *
 * @copyright   Copyright (C) 2009 - 2020 NICK.SPENCER All rights reserved.
 * @license     GNU GPL v3 or later
 **/

/*This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

defined('_JEXEC') or die;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Factory;

/**
 * Joomla! SeersCookieConsent plugin.
 *
 * @since  1.5
 */
class PlgSystemSeersCookieConsent extends JPlugin
{
	private $cookieid;
	private $seers_email;
	private $seers_url;
	private $langTag; 
	

	public function onAfterRender()
	{
		$app =jFactory::getApplication();
		if ($app->isClient('site') == false)
		{
			return;
		}
        
        $db= JFactory::getDbo();
		$query = "SELECT id,url,email,apikey,hitcount
				  FROM #__seers_cookie_consent order by id desc";
		$db->setQuery($query);
		$last_row = $db->loadObject();
		if($last_row && $last_row->id){
			$this->seers_url = $last_row->url;
			$this->seers_email = $last_row->email;
			$this->cookieid = $last_row->apikey;
		}

		$my_url = $this->params->get('my_url');
		$my_email = $this->params->get('my_email');
		


		$body = $app->getBody();
        $cookie_script = sprintf('<head><script id="SeersCookieConcent" data-key="%s" data-name="CookieXray" src="https://seersco.com/script/cb.js" type="text/javascript"></script>', $this->cookieid);
		$body = str_replace('<head>', $cookie_script , $body);
		$app->setBody($body);
	}
	
	function onContentPrepareForm($form, $data)
	{
	            
    $db= JFactory::getDbo();
		$query = "SELECT id,url,email,apikey,hitcount
				  FROM #__seers_cookie_consent order by id desc";
		$db->setQuery($query);
		$last_row = $db->loadObject();
		if($last_row && $last_row->id){
			$this->seers_url = $last_row->url;
			$this->seers_email = $last_row->email;
			$this->cookieid = $last_row->apikey;
		}
		if($this->cookieid){
		   $form->setFieldAttribute('domain_group_id', 'default' , $this->cookieid  , 'params');
		}
		if($this->seers_url){
		   $form->setFieldAttribute('my_url', 'default' , $this->seers_url  , 'params');
		}
		if($this->seers_email){
		   $form->setFieldAttribute('my_email', 'default' , $this->seers_email  , 'params');
		}
		if ($this->params->get('domain_group_id') == ''){

		$uri = JUri::getInstance();
		$my_url = $uri->root();

		$user = Factory::getUser('admin');
		$my_email = $user->get('email');
		

		$form->setFieldAttribute('my_url', 'default' , $my_url , 'params');
		$form->setFieldAttribute('my_email', 'default' , $my_email  , 'params');


		$my_url = $this->params->get('my_url');
		$my_email = $this->params->get('my_email');
		$form->setFieldAttribute('domain_group_id', 'default' , $this->cookieid  , 'params');

	}

	}

	private function Seers_API ($my_url, $my_email){
		$this->langTag = JComponentHelper::getParams('com_languages')->get('site');
		$postData = array(
			'domain'=>$my_url,
			'email'=>$my_email,
			'secret'=>'---------secretkey--------------------',
			'platform'=>'joomla',
			'lang' => str_replace("-","_",$this->langTag),
		);
		
		$request_headers = array(
			'Content-Type: application/json',
			'Referer: '.$my_url
		);
		$url = "https://seersco.com/api/get-key-for-joomla";
		$postdata = json_encode($postData);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$response  = json_decode($result,true);


		//$this->cookieid = '';
		if ($response){
		    //counter for api start
		    
		    $profile = new stdClass();
		
		    $db= JFactory::getDbo();
		    $query = "SELECT id,url,email,apikey,hitcount
				  		FROM #__seers_cookie_consent order by id desc";
		    $db->setQuery($query);
		    $last_row = $db->loadObject();
		    if(isset($last_row) && isset($last_row->id)){
			    $profile->id = $last_row->id;
			    $profile->hitcount = intval($last_row->hitcount) + 1;
			    $result = JFactory::getDbo()->updateObject('#__seers_cookie_consent', $profile,'id');
		    }
		    
		    
		    //counter for api end
		    
		    
			return( $response['key']);
		}else{
			return '';
		}
	}
	public function onExtensionAfterSave(){

	}
	
	public function onExtensionBeforeSave($context, $item, $isNew){
		
		if ($context !== "com_plugins.plugin" || $item->type !== "plugin")
		{
			return true;
		}
                
                //read the xml file of this plugin and use this xml to get the plugin name
                $xmlfile = simplexml_load_file(__DIR__ . "/" . $item->element . ".xml");
                
		$params = new JRegistry($item->params);
		$my_url = $params["my_url"];
		$my_email = $params["my_email"];
		$this->cookieid = $this->Seers_API($my_url, $my_email);
		
		$this->Seers_Plugin_API($my_url, $item->enabled, ((!empty($xmlfile->name)) ? (string)$xmlfile->name : '' ));
		
		$profile = new stdClass();
		$profile->url=$my_url;
		$profile->email=$my_email;
		$profile->apikey=$this->cookieid;
		

		$db= JFactory::getDbo();
		$query = "SELECT id,url,email,apikey,hitcount
				  		FROM #__seers_cookie_consent order by id desc";
		$db->setQuery($query);
		$last_row = $db->loadObject();
		if(isset($last_row) && isset($last_row->id)){
			$profile->id = $last_row->id;
			$result = JFactory::getDbo()->updateObject('#__seers_cookie_consent', $profile,'id');
		}
		else{
			$result = JFactory::getDbo()->insertObject('#__seers_cookie_consent', $profile);
		}
		
	}

        private function Seers_Plugin_API ($my_url, $enabled, $pluginname){
		$postData = array(
			'domain'=>$my_url,
			'isactive'=>$enabled,
			'secret'=>'------------------secretkey------------------------------',
			'platform'=>'joomla',
			'pluginname' => $pluginname
		);
		
		$request_headers = array(
			'Content-Type: application/json',
			'Referer: '.$my_url
		);
		$url = "https://cmp.seersco.com/api/plugin-domain";
		$postdata = json_encode($postData);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
		$result = curl_exec($ch);
		curl_close($ch);
		
		$response  = json_decode($result,true);
}

}
