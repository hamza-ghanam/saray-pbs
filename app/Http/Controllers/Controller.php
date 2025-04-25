<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[
    OA\Info(title: "Saray Property Booking System", version: "2.0"),
    OA\server(url: 'http://localhost:8000/api', description: 'URL'),
    OA\securityScheme(securityScheme: 'bearerAuth', type: 'http', name: 'Authorization', in: 'header', scheme: 'bearer'),
]
abstract class Controller
{
    //
}
