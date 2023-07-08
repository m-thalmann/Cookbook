<?php

namespace App\Traits\Models;

use DateTimeInterface;

trait SerializesDatesToTimestamp {
    protected function serializeDate(DateTimeInterface $date) {
        return $date->getTimestamp();
    }
}
