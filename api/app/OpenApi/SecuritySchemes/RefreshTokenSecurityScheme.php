<?php

namespace App\OpenApi\SecuritySchemes;

use GoldSpecDigital\ObjectOrientedOAS\Objects\SecurityScheme;
use Vyuldashev\LaravelOpenApi\Factories\SecuritySchemeFactory;

class RefreshTokenSecurityScheme extends SecuritySchemeFactory {
    public function build(): SecurityScheme {
        return SecurityScheme::create('RefreshToken')
            ->type(SecurityScheme::TYPE_HTTP)
            ->scheme('bearer')
            ->description(
                'Long-lived refresh token used to retrieve a new access token'
            );
    }
}

