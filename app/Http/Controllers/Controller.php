<?php

namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     title="Saray Property Booking System",
 *     version="2.0"
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Main API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     in="header",
 *     name="Authorization"
 * )
 */
abstract class Controller
{
    //
}
