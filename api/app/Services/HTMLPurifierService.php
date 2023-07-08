<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HTMLPurifierService {
    private HTMLPurifier $purifier;

    public function __construct(HTMLPurifier $purifier) {
        $this->purifier = $purifier;
    }

    /**
     * Filters an HTML snippet/document to be XSS-free and standards-compliant.
     *
     * @param string|null $html String of HTML to purify
     * @param HTMLPurifier_Config $config Config object for this operation.
     *
     * @see HTMLPurifier::purify()
     *
     * @return string Purified HTML
     */
    public function purify($html, $config = null) {
        if ($html === null) {
            return null;
        }

        return $this->purifier->purify($html, $config);
    }
}
