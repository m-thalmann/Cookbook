<?php

namespace Tests\Feature\Services;

use App\Providers\HTMLPurifierServiceProvider;
use App\Services\HCaptchaService;
use App\Services\HTMLPurifierService;
use HTMLPurifier;
use Tests\TestCase;

class HTMLPurifierServiceTest extends TestCase {
    private $service;

    public function setUp(): void {
        parent::setUp();

        $this->service = new HTMLPurifierService(
            new HTMLPurifier(HTMLPurifierServiceProvider::getDefaultConfig())
        );
    }

    public function testPurifiesHTML() {
        $this->assertEquals(
            '',
            $this->service->purify("<script>console.log('test');</script>")
        );
    }
}
