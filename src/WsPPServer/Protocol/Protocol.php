<?php
namespace WsPPServer\Protocol;

/**
 * 
 *  Protocole { methode: <string>, subscription: <string>, datas: <mixed> }
 *
 */
class Protocol  
{
	protected $raw_string;
	protected $methode;
	protected $subscription;
	protected $datas;
	
	public function __construct($datas)
	{
		$this->resetDatas($datas);
		$this->parse();
	}
	
	public function resetDatas($datas) 
	{
		$this->raw_string   = $datas;
		$this->methode      = null;
		$this->subscription = null;
		$this->datas        = null;
	}
	
	public function getRawString()
	{
		return $this->raw_string;
	}
	
	public function getDatas()
	{
		return $this->datas;
	}
	
	public function getMethode()
	{
		return $this->methode;
	}
	
	public function getSubscription()
	{
		return $this->subscription;
	}
	
	protected function parse()
	{
		$jdata = json_decode($this->raw_string, true);

		$this->methode       = $jdata['methode'];
		$this->subscription  = $jdata['subscription'];
		
		if(isset($jdata['datas'])) {
			$this->datas = $jdata['datas'];
		}
	}
}