<?php

namespace App\OpenApi\SecuritySchemes;

use GoldSpecDigital\ObjectOrientedOAS\Objects\SecurityScheme;
use Vyuldashev\LaravelOpenApi\Factories\SecuritySchemeFactory;

class AccessTokenSecurityScheme extends SecuritySchemeFactory {
    public function build(): SecurityScheme {
        return SecurityScheme::create('AccessToken')
            ->type(SecurityScheme::TYPE_HTTP)
            ->scheme('bearer')
            ->description(
                'Short-lived access token used to retrieve data from the api'
            );
    }
}

