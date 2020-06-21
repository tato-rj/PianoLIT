<?php

namespace App\Shop\Contract;

interface Merchandise
{
	public function purchases();
	public function getPriceInCentsAttribute();
	public function isFree();
}
