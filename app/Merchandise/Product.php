<?php

namespace App\Merchandise;

use App\ShareableContent;
use App\Traits\Filterable;

abstract class Product extends ShareableContent
{
	use Filterable;

	public function purchaseRoute()
	{
		return route('shop.purchase', ['model' => get_class($this), 'reference' => $this]);
	}

	public function getProductNameAttribute()
	{
		return class_basename($this);
	}

	public function generateKeywords()
	{
        $title = $this->title . ' sheet music';

        $composer = $this->piece()->exists() ? $this->piece->composer->name . ' sheet music' : null;

        $words = array_values(array_filter([$title, $composer]));

        return arrayToSentence($words, ',', ',') . ',scores,sheet music';
	}
}
