<?php

/**
 * @OA\Tag(
 * name="Auth",
 * description="Operations related to authentication"
 * )
 */

/**
 * @OA\Post(
 * path="/api/register",
 * summary="Register a new user",
 * description="Registers a new user with the provided details.",
 * tags={"Auth"},
 * @OA\RequestBody(ref="#/components/requestBodies/RegisterRequest"),
 * @OA\Response(response=201, ref="#/components/responses/RegisterSuccess"),
 * @OA\Response(response=400, ref="#/components/responses/BadRequestResponse"),
 * @OA\Response(response=500, ref="#/components/responses/InternalServerErrorResponse")
 * )
 */

/**
 * @OA\Post(
 * path="/api/login",
 * summary="Authenticate user and generate token",
 * description="Authenticates the user with username and password and returns an access token.",
 * tags={"Auth"},
 * @OA\RequestBody(ref="#/components/requestBodies/LoginRequest"),
 * @OA\Response(response=200, ref="#/components/responses/LoginSuccess"),
 * @OA\Response(response=401, ref="#/components/responses/UnauthorizedResponse"),
 * @OA\Response(response=400, ref="#/components/responses/BadRequestResponse")
 * )
 */

/**
 * @OA\Post(
 * path="/api/logout",
 * summary="Logout the current user (revoke token)",
 * description="Invalidates the current user's token.",
 * tags={"Auth"},
 * security={{"bearerAuth":{}}},
 * @OA\Response(response=200, ref="#/components/responses/LogoutSuccess")
 * )
 */

/**
 * @OA\Post(
 * path="/api/validate-token",
 * summary="Validate the provided token",
 * description="Validates the provided token and returns user information if valid. Requires the X-Device-ID header.",
 * tags={"Auth"},
 * security={{"bearerAuth":{}}},
 * @OA\Header(
 * header="X-Device-ID",
 * required=true,
 * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
 * ),
 * @OA\Response(response=200, ref="#/components/responses/ValidateTokenSuccess"),
 * @OA\Response(response=400, ref="#/components/responses/BadRequestResponse"),
 * @OA\Response(response=401, ref="#/components/responses/UnauthorizedResponse"),
 * @OA\Response(response=404, ref="#/components/responses/NotFoundResponse"),
 * @OA\Response(response=500, ref="#/components/responses/InternalServerErrorResponse")
 * )
 */