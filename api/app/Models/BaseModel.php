<?php

namespace App\Models;

use App\Traits\SerializesDateToTimestamp;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model {
    use SerializesDateToTimestamp;
}
