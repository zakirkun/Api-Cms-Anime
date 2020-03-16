<?php

namespace App\Helpers\V1\Adfly;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Helpers\V1\Adfly\httpAdfly ;

class AuthType {
	const BASIC = 1;
	const HMAC = 2;
}