<?php

namespace App\Tools;

class Traffic
{
	protected $ignoreIP = [
		'73.126.137.37', 
		'24.215.182.64', 
		'69.142.144.48'
	];
	// 284 -> Arthur, 260 -> Elena, 196 -> Mark Twain, 249 -> Tester, 928 -> Krusty
	protected $ignoreID = [284, 260, 196, 249, 928];

	public function isRealVisitor()
	{
		return ! in_array(request()->ip(), $this->ignoreIP) && ! \App\Activity\ExcludedAccounts::request(request());
	}

	public function isRealUser($id)
	{
		return ! in_array($id, $this->ignoreID) && ! \App\Activity\ExcludedAccounts::id($id);
	}
}
