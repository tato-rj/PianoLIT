<?php

namespace App\Shop\Contract;

interface Merchandise
{
	public function purchases();
	public function getPriceInCentsAttribute();
	public function isFree();
	public function finalPrice();
	public function notification();
	public function url();
	public function links();
}
