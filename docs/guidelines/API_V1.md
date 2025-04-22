# API Response Standards

**Last Updated**: 2025-04-17  
**Version**: v1

## Response Format

All API responses (success/error) MUST follow this structure:

```json
{
    "status": "success | error",
    "data": "payload (object/array) | null",
    "error": {
        "code": "HTTP status code (e.g., 401)",
        "message": "Human-readable error summary",
        "details": "Validation errors or additional context (optional)"
    },
    "last_updated": "ISO 8601 timestamp (e.g., 2025-04-17T14:30:45.000000Z)",
    "message": "Optional success/error description"
}
```

## Success Examples

### 200 OK (Profile Success)

```json
{
    "status": "success",
    "data": {
        "user": {
            "name": "John Doe",
            "username": "johndoe",
            "email": "john@example.com",
            "phone": "1234567890",
            "dob": "1990-01-01",
            "address": "123 Main St",
            "created_at": "2025-04-17T14:30:00.000000Z",
            "updated_at": "2025-04-17T14:30:00.000000Z"
        },
        "dataPegawaiSimpeg": {
            "id_pegawai": "uuid-123",
            "nip": "123456789012345678",
            "nama_lengkap": "John Doe",
            "office": {
                "id_instansi": "instansi-123",
                "nama_instansi": "Example Agency"
            }
        },
        "dataPegawaiAbsen": null,
        "dataPegawaiEkinerja": null
    },
    "error": null,
    "last_updated": "2025-04-17T14:30:45.000000Z",
    "message": "Profile retrieved successfully"
}
```

### 200 OK (Apps Data Success)

```json
{
    "status": "success",
    "data": {
        "userAbsen": {
            "id": "uuid-456",
            "name": "johndoe",
            "phone": "1234567890"
        },
        "userEkinerja": {
            "UID": "johndoe",
            "nama": "John Doe",
            "NIP": "123456789012345678"
        }
    },
    "error": null,
    "last_updated": "2025-04-17T14:31:20.000000Z",
    "message": "Apps data retrieved successfully"
}
```

## Error Examples

### 401 Unauthorized (Profile/Apps)

```json
{
    "status": "error",
    "data": null,
    "error": {
        "code": 401,
        "message": "User not authenticated"
    },
    "last_updated": "2025-04-17T14:32:30.000000Z",
    "message": null
}
```

### 500 Internal Server Error

```json
{
    "status": "error",
    "data": null,
    "error": {
        "code": 500,
        "message": "Internal server error",
        "details": "Database connection failed"
    },
    "last_updated": "2025-04-17T14:33:05.000000Z",
    "message": null
}
```

## Implementation Checklist

### Backend (Laravel)

-   [x] Use ApiResponseTrait in all controllers
-   [x] Add last_updated to every response (handled by ApiResponseTrait)
-   [x] Ensure error.details includes validation errors
-   [x] Write unit tests for response formats
-   [x] Implement refresh token mechanism
-   [x] Standardize ProfileController responses

### Frontend/Android (Flutter)

-   [ ] Parse status field
-   [ ] Use last_updated for caching
-   [ ] Display error.message to users
-   [ ] Implement token refresh logic

### Frontend (Vue.js)

-   [ ] Parse status field
-   [ ] Use last_updated for caching
-   [ ] Display error.message to users
-   [ ] Implement token refresh logic

### Documentation

-   [x] Update this file when adding new fields
-   [x] Add endpoint-specific examples for authentication
-   [x] Add endpoint-specific examples for profile

## Notes

1. Timestamps: Use ISO 8601 format
2. Null Fields: Omit if unused
3. Consistency: All endpoints must follow this standard
