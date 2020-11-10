<?php

namespace App\Services\SEO;

use App\Services\SEO\Traits\{Keywords, About};

class SEO
{
	use Keywords, About;

	public function keywords()
	{
		return implode(', ', $this->keywords);
	}

	public function about($attr)
	{
		if (property_exists($this, $attr))
			return $this->$attr;
	}
}