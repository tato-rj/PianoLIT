<?php

namespace App\Tools;

class Traffic
{
	protected $ignoreIP = [
		'73.126.137.37', 
		'24.215.182.64', 
		'104.129.138.238'
	];

	protected $ignoreID = [260, 196, 249];

	public function isRealVisitor()
	{
		return ! in_array(request()->ip(), $this->ignoreIP);
	}

	public function isRealUser($id)
	{
		return ! in_array($id, $this->ignoreID);
	}
}
