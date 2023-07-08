<?php

namespace App\Models;

use App\Traits\Models\SerializesDatesToTimestamp;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    use SerializesDatesToTimestamp;
}
