<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[
    OA\Info(version: '1.0', description: 'Saray Property Booking System', title: 'Saray PBS'),
    OA\server(url: 'http://127.0.0.1:8000/api', description: 'IP'),
    OA\server(url: 'http://localhost:8000/api', description: 'URL'),
    OA\securityScheme(securityScheme: 'bearerAuth', type: 'http', name: 'Authorization', in: 'header', scheme: 'bearer'),
]
abstract class Controller
{
    //
}
