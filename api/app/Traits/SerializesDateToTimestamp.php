<?php

namespace App\Traits;

use DateTimeInterface;

trait SerializesDateToTimestamp {
    protected function serializeDate(DateTimeInterface $date) {
        return $date->getTimestamp();
    }
}
