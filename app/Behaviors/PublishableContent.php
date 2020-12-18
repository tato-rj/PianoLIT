<?php

namespace App\Behaviors;

use App\PianoLit;
use App\Traits\Publishable;

abstract class PublishableContent extends PianoLit
{
	use Publishable;
}