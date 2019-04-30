<?php

namespace App\Tools;

class Traffic
{
	protected $ignore = ['73.126.141.15', '24.215.182.64', '104.129.138.238'];

	public function isRealVisitor()
	{
		return ! in_array(request()->ip(), $this->ignore);
	}
}
