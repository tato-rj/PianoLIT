<?php

namespace App\Merchandise;

use App\ShareableContent;

abstract class Product extends ShareableContent
{
	public function purchaseRoute()
	{
		return route('shop.purchase', ['model' => get_class($this), 'reference' => $this]);
	}

	public function shelf_image()
	{
		return storage($this->shelf_cover_path);
	}
}
