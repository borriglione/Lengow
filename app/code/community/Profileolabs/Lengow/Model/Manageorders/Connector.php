<?php

class LengowConnector
{
	public $lengow_token;
	public $idClient;
 
	public $domain = 'solution.lengow.com/wsdl';
	public $protocol = 'https';
 
	private function authentification()
	{
		$call = $this->protocol.'://'.$this->domain.'/connector/call.json?token='.$this->lengow_token.'&idClient='.$this->idClient.'&method=authentification';
 
		$o = $this->make_call($call, 'authentification');
		return $o['return'];
	}
 
	public function callMethod($method, $array)
	{
		if($this->authentification() == 'Ok')
		{
			$call = $this->protocol.'://'.$this->domain.'/connector/call.json?token='.$this->lengow_token.'&idClient='.$this->idClient.'&method='.$method.'&array='.urlencode(serialize($array));

			return $this->make_call($call);
		}
		/*
		else
		{
			die("Die. No Authentification.");
		}
		//*/
	}
 
	public function getCommands($array)
	{
		$feed = 'http://api.lengow.com/'.$array['dateFrom'].'/'.$array['dateTo'].'/'.$this->idClient.'/'.$array['id'].'/commands/';
		$orders = simplexml_load_file($feed);
		return $orders;
	}
 
	public function getStatistics($array)
	{
		$feed = 'http://statistics.lengow.com/'.$array['dateFrom'].'/'.$array['dateTo'].'/'.$this->idClient.'/'.$array['id'].'/total-All/';
		$statistics = simplexml_load_file($feed);
		return $statistics;
	}
 
	private function make_call($call, $method = '')
	{
		$ch = curl_init($call);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$data = curl_exec($ch);
		curl_close($ch);
 
		if($method == 'authentification')
		return $this->parseJson($data, true);
		else
		return $data;
	}
 
	public function parseJson($data)
	{
		return json_decode($data, true);
	}
}

?>