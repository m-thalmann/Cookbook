<?php

namespace App\Services;

class HCaptchaService {
    private const VERIFY_URL = 'https://hcaptcha.com/siteverify';

    /**
     * The hCaptcha server secret
     *
     * @var string
     */
    private $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    /**
     * Verifies the sent hcaptcha verify token
     *
     * @param string $token
     *
     * @return bool
     */
    public function verify($token) {
        $response = httpClient()
            ->asForm()
            ->post(self::VERIFY_URL, [
                'response' => $token,
                'secret' => $this->secret,
            ]);

        return $response->ok() && $response->json('success');
    }
}
