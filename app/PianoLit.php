<?php

namespace App;

use App\Traits\BelongsToThrough;
use Illuminate\Database\Eloquent\Model;

class PianoLit extends Model
{
	use BelongsToThrough;
	
	protected $guarded = [];
}
