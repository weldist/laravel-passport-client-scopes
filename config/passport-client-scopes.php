<?php

declare(strict_types=1);

return [

    /*
     * Whether a client whose oauth_clients.scopes column is null may receive
     * the scopes it requests on a client credentials token. When disabled,
     * such a client receives no scopes until its column is filled.
     */
    'allow_unconfigured_clients' => false,

    /*
     * Add the RFC 6750 WWW-Authenticate challenge to 401 responses caused by
     * a missing or invalid Passport bearer token.
     */
    'authentication_challenge' => true,

];
