<?php

namespace Tests\Feature\Services;

use App\Services\HCaptchaService;
use Tests\TestCase;

class HCaptchaServiceTest extends TestCase {
    private $service;

    const SECRET = '0x0000000000000000000000000000000000000000';
    const VALID_TEST_TOKEN = '10000000-aaaa-bbbb-cccc-000000000001';
    const INVALID_TEST_TOKEN = '40000000-aaaa-bbbb-cccc-000000000003';

    public function setUp(): void {
        parent::setUp();

        $this->service = new HCaptchaService(self::SECRET);
    }

    public function testVerifyValidToken() {
        $this->assertTrue($this->service->verify(self::VALID_TEST_TOKEN));
    }
    public function testVerifyInvalidToken() {
        $this->assertFalse($this->service->verify(self::INVALID_TEST_TOKEN));
    }
}
