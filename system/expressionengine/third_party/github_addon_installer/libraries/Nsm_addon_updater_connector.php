<?php

class Nsm_addon_updater_connector
{
	protected $EE;
	protected $fetch_method = 'file_get_contents';
	protected $addons;
	protected $versions;
	protected $configs;
	
	public function __construct()
	{
		$this->EE =& get_instance();
		
		if (isset($params['fetch_method']))
		{
			$this->fetch_method = $params['fetch_method'];
		}
	}
	
	protected function load_addons()
	{
		if ( ! is_null($this->addons))
		{
			return;
		}
		
		$this->EE->load->library('addons');
		
		foreach(array_keys($this->EE->addons->_packages) as $addon)
		{
			$xml = NULL;
			
			if (file_exists(PATH_THIRD.'/'.$addon.'/config.php'))
			{
				include PATH_THIRD.'/'.$addon.'/config.php';
				
				$this->configs[$addon] = $config;
				
				if (isset($this->configs[$addon]['nsm_addon_updater']['versions_xml']))
				{
					$path = APPPATH.'cache/Nsm_addon_updater_acc/'.md5($this->configs[$addon]['nsm_addon_updater']['versions_xml']).'.xml';
					
					if (file_exists($path))
					{
						$xml = read_file($path);
					}
					
					//this actually does garbage collection/refreshing of the cache
					//but let's leave that up to the Acc and only grab it if cache already exists
					/*
					if (file_exists($path) && (time() - filemtime($path)) < 86400)
					{
						$xml = read_file($path);
					}
					else
					{
						$xml = call_user_func($this->fetch_method, $this->configs[$addon]['nsm_addon_updater']['versions_xml']);
						
						write_file($path, ($xml) ? $xml : '0');
					}
					*/
					
					if ( ! empty($xml))
					{
						$this->addons[$addon] = $xml;
					}
				}
			}
		}
	}
	
	public function get_versions()
	{
		if ( ! is_null($this->versions))
		{
			return $this->versions;
		}
		
		$this->load_addons();
		
		$this->EE->load->helper(array('array', 'file'));
		
		$this->versions = array();
		
		foreach ($this->addons as $addon => $xml)
		{
			$xml = @simplexml_load_string($xml);
			
			if ( ! $xml)
			{
				continue;
			}
			
			foreach ($xml->channel->item as $item)
			{
				$version = (string) @$item->children(element('ee_addon', $xml->getNameSpaces(TRUE)))->version;
				
				if ($version && version_compare($this->configs[$addon]['version'], $version, '<'))
				{
					$this->versions[$addon] = $version;
				}
			}
		}
		
		return $this->versions;
	}
}