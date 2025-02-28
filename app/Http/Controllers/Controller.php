<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[
    OA\Info(title: "Property Booking System", version: "1.0"),
    OA\server(url: 'http://127.0.0.1:8000/api', description: 'IP'),
    OA\server(url: 'http://localhost:8000/api', description: 'URL'),
    OA\securityScheme(securityScheme: 'bearerAuth', type: 'http', name: 'Authorization', in: 'header', scheme: 'bearer'),
]
abstract class Controller
{
    //
}
