## 1. AuthController

### POST /auth/login

**Description**: Authenticate a user and issue an access token along with a refresh token. Requires the `X-Device-ID` header for rate limiting and device tracking.
**Authentication**: None
**Headers**:

-   `X-Device-ID`: Unique device identifier (required, minimum 8 alphanumeric characters, underscores, or hyphens)
    **Request Body**:

```json
{
    "username": "string (required)",
    "password": "string (required)"
}
Response:
	•	200 OK: JSON{
	•	    "status": "success",
	•	    "data": {
	•	        "access_token": "string",
	•	        "token_type": "Bearer",
	•	        "refresh_token": "string",
	•	        "user": {
	•	            "id": "integer",
	•	            "username": "string",
	•	            "email": "string | null"
	•	        },
	•	        "expires_at": "ISO 8601 timestamp"
	•	    },
	•	    "error": null,
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Login successful"
	•	}
	•
	•	400 Bad Request: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 400,
	•	        "message": "Invalid input. Please check your username and password.",
	•	        "details": {
	•	            "username": ["The username field is required."],
	•	            "password": ["The password field is required."]
	•	        }
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Invalid input. Please check your username and password."
	•	}
	•	(Also includes details for missing or invalid X-Device-ID)
	•	401 Unauthorized: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 401,
	•	        "message": "Invalid login credentials.",
	•	        "details": null
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Invalid login credentials."
	•	}
	•
	•	429 Too Many Requests: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 429,
	•	        "message": "Too many login attempts. Please try again later.",
	•	        "details": {
	•	            "retry_after": "integer"
	•	        }
	•	    },
	•	    "meta": {
	•	        "retry_after": "integer"
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": null
	•	}
	•
Notes:
	•	Rate limiting: 5 attempts per minute per username + device ID.
	•	The meta.retry_after field indicates seconds until the next attempt is allowed.
	•	The X-Device-ID header is crucial for rate limiting and helps in identifying the device used for login.
POST /auth/refresh-token
Description: Refresh an access token using a valid refresh token. Requires the X-Device-ID header for device validation.
Authentication: None
Headers:
	•	X-Device-ID: Unique device identifier (required, minimum 8 alphanumeric characters, underscores, or hyphens) Request Body:
JSON



{
    "refresh_token": "string (required)"
}
Response:
	•	200 OK: JSON{
	•	    "status": "success",
	•	    "data": {
	•	        "access_token": "string",
	•	        "token_type": "Bearer",
	•	        "refresh_token": "string",
	•	        "expires_at": "ISO 8601 timestamp"
	•	    },
	•	    "error": null,
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Token refreshed successfully"
	•	}
	•
	•	400 Bad Request: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 400,
	•	        "message": "Invalid input. Please provide a valid refresh token.",
	•	        "details": {
	•	            "refresh_token": ["The refresh token field is required."]
	•	        }
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Invalid input. Please provide a valid refresh token."
	•	}
	•	(Also includes details for missing or invalid X-Device-ID)
	•	401 Unauthorized: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 401,
	•	        "message": "Invalid or expired refresh token. Please login again.",
	•	        "details": null
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Invalid or expired refresh token. Please login again."
	•	}
	•
	•	404 Not Found: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 404,
	•	        "message": "User not found.",
	•	        "details": null
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "User not found."
	•	}
	•
POST /auth/validate-token
Description: Validate an access token provided in the Authorization: Bearer <token> header. Requires the X-Device-ID header for device validation.
Authentication: Required (api.auth)
Headers:
	•	Authorization: Bearer <token> (required)
	•	X-Device-ID: Unique device identifier (required, minimum 8 alphanumeric characters, underscores, or hyphens) Request Body: None Response:
	•	200 OK: JSON{
	•	    "status": "success",
	•	    "data": {
	•	        "is_valid": true,
	•	        "user": {
	•	            "id": "integer",
	•	            "username": "string",
	•	            "email": "string | null"
	•	        },
	•	        "token": {
	•	            "id": "string",
	•	            "expires_at": "ISO 8601 timestamp | null",
	•	            "last_used_at": "ISO 8601 timestamp | null",
	•	            "device_id": "string | null"
	•	        }
	•	    },
	•	    "error": null,
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Token is valid"
	•	}
	•
	•	400 Bad Request: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 400,
	•	        "message": "Device ID is required. Please include X-Device-ID in the request header.",
	•	        "details": {
	•	            "device_id": "The X-Device-ID header is required."
	•	        }
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Device ID is required. Please include X-Device-ID in the request header."
	•	}
	•	(Also includes details for invalid X-Device-ID format)
	•	401 Unauthorized: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 401,
	•	        "message": "No token provided. Please include a valid Bearer token.",
	•	        "details": null
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "No token provided. Please include a valid Bearer token."
	•	}
	•	(Also for invalid or expired token, and device mismatch)
	•	404 Not Found: JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 404,
	•	        "message": "User not found for this token.",
	•	        "details": null
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "User not found for this token."
	•	}
	•
	•	500 Internal Server Error: (For unexpected errors during validation)
POST /auth/logout
Description: Invalidate the current access token and refresh token for the authenticated user.
Authentication: Required (api.auth)
Request Body: None
Response:
	•	200 OK: JSON{
	•	    "status": "success",
	•	    "data": null,
	•	    "error": null,
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Logged out successfully"
	•	}
	•
	•	401 Unauthorized: (If token is invalid or not provided) JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 401,
	•	        "message": "Unauthorized",
	•	        "details": null
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": null
	•	}
	•
POST /auth/change-password
Description: Change the authenticated user's password.
Authentication: Required (api.auth)
Request Body:
JSON



{
    "current_password": "string (required)",
    "new_password": "string (required, min 8 chars, requires uppercase, lowercase, number, and symbol, not a common password)",
    "new_password_confirmation": "string (required, must match new_password)"
}
Response:
	•	200 OK: JSON{
	•	    "status": "success",
	•	    "data": null,
	•	    "error": null,
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Password changed successfully"
	•	}
	•
	•	400 Bad Request: (For invalid input, including password requirements mismatch or current password incorrect) JSON{
	•	    "status": "error",
	•	    "data": null,
	•	    "error": {
	•	        "code": 400,
	•	        "message": "Invalid input. Please check your password requirements.",
	•	        "details": {
	•	            "current_password": ["The current password field is required."],
	•	            "new_password": ["The new password field is required.", "The new password must be at least 8 characters.", ...],
	•	            "new_password_confirmation": ["The new password confirmation field is required.", "The new password confirmation does not match."]
	•	        }
	•	    },
	•	    "last_updated": "2025-04-25T08:15:00+00:00",
	•	    "message": "Invalid input. Please check your password requirements."
	•	}
	•
```
