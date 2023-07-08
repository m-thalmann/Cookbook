<?php

namespace App\OpenApi\SecuritySchemes;

use GoldSpecDigital\ObjectOrientedOAS\Objects\SecurityScheme;
use Vyuldashev\LaravelOpenApi\Factories\SecuritySchemeFactory;

class OptionalAccessTokenSecurityScheme extends SecuritySchemeFactory {
    public function build(): SecurityScheme {
        return SecurityScheme::create('OptionalAccessToken')
            ->type(SecurityScheme::TYPE_HTTP)
            ->scheme('bearer')
            ->description(
                'One of the following:' .
                    "\n- Short-lived access token used to retrieve data from the api (bearer token)" .
                    "\n- No authentication"
            );
    }
}
