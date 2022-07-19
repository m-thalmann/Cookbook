<?php

namespace Tests\Feature\Services;

use App\Services\HCaptchaService;
use Tests\TestCase;

class HCaptchaServiceTest extends TestCase {
    private $service;

    public function setUp(): void {
        parent::setUp();

        $this->service = new HCaptchaService(
            '0x0000000000000000000000000000000000000000'
        );
    }

    public function testVerifyValidToken() {
        $this->assertTrue(
            $this->service->verify(HCaptchaService::VALID_TEST_TOKEN)
        );
    }
    public function testVerifyInvalidToken() {
        $this->assertFalse(
            $this->service->verify(HCaptchaService::INVALID_TEST_TOKEN)
        );
    }
}
