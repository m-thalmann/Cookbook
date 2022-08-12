<?php

namespace App\Models;

use App\Traits\Models\SerializesDatesToTimestamp;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BasePivot extends Pivot {
    use SerializesDatesToTimestamp;
}
