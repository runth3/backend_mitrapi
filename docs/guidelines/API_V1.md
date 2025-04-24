# API Documentation

This document outlines the API endpoints for the application, adhering to the standardized response structure. All endpoints use JSON for request and response bodies. The base URL is assumed to be `/api`.

## Authentication

-   Most endpoints require authentication via the `Authorization: Bearer <token>` header, enforced by the `api.auth` middleware.
-   Admin-only endpoints require the `admin` middleware, restricting access to users with `is_admin = true`.
-   The `X-Device-ID` header is required for `/auth/login` to support user-based rate limiting.

## Response Structure

All responses follow this structure:

```json
{
    "status": "success | error",
    "data": "payload | null",
    "error": {
        "code": "HTTP status code",
        "message": "Error summary",
        "details": "Optional validation errors"
    },
    "meta": "Optional metadata (e.g., pagination)",
    "last_updated": "ISO 8601 timestamp",
    "message": "Optional description"
}
```

-   The `meta` field is included only when additional metadata (e.g., pagination) is required.
-   The `error.details` field contains validation errors when applicable.

---

## 1. AuthController

### POST /auth/login

**Description**: Authenticate a user and issue an access token.
**Authentication**: None
**Headers**:

-   `X-Device-ID`: Unique device identifier (required for rate limiting)
    **Request Body**:

```json
{
    "username": "string",
    "password": "string"
}
```

**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "user": {
                "id": "integer",
                "name": "string",
                "username": "string",
                "email": "string | null",
                "created_at": "ISO 8601 timestamp",
                "updated_at": "ISO 8601 timestamp"
            },
            "token": "string",
            "refresh_token": "string"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Login successful"
    }
    ```
-   **429 Too Many Requests** (rate limit exceeded):
    ```json
    {
        "status": "error",
        "data": null,
        "error": {
            "code": 429,
            "message": "Too many login attempts",
            "details": null
        },
        "meta": {
            "retry_after": "integer"
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": null
    }
    ```
    **Notes**:
-   Rate limiting: 5 attempts per minute per username + device ID.
-   The `meta.retry_after` field indicates seconds until the next attempt is allowed.

### POST /auth/refresh-token

**Description**: Refresh an access token using a refresh token.
**Authentication**: None
**Request Body**:

```json
{
    "refresh_token": "string"
}
```

**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "token": "string",
            "refresh_token": "string"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Token refreshed successfully"
    }
    ```
-   **401 Unauthorized**:
    ```json
    {
        "status": "error",
        "data": null,
        "error": {
            "code": 401,
            "message": "Invalid refresh token",
            "details": null
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": null
    }
    ```

### POST /auth/validate-token

**Description**: Validate an access token.
**Authentication**: Required (`api.auth`)
**Request Body**: None
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "user": {
                "id": "integer",
                "name": "string",
                "username": "string",
                "email": "string | null",
                "created_at": "ISO 8601 timestamp",
                "updated_at": "ISO 8601 timestamp"
            }
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Token is valid"
    }
    ```

---

## 2. ProfileController

### GET /profile/me

**Description**: Retrieve the authenticated user's profile data.
**Authentication**: Required (`api.auth`)
**Query Parameters**: None
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "user": {
                "name": "string",
                "username": "string",
                "email": "string | null",
                "phone": "string | null",
                "dob": "date | null",
                "address": "string | null",
                "created_at": "ISO 8601 timestamp",
                "updated_at": "ISO 8601 timestamp"
            },
            "data_pegawai_simpeg": {
                "id_pegawai": "string",
                "nip": "string",
                "nama_lengkap": "string",
                "gelar": "string",
                "tempat_lahir": "string",
                "tgl_lahir": "date",
                "jenis_kelamin": "string",
                "id_pangkat": "integer",
                "id_instansi": "string",
                "id_unit_kerja": "string",
                "id_sub_unit_kerja": "string",
                "id_jabatan": "integer",
                "tmt_jabatan": "date",
                "id_eselon": "integer",
                "alamat": "string",
                "no_telp": "string",
                "office": {
                    "id_instansi": "string",
                    "nama_instansi": "string"
                }
            },
            "data_pegawai_absen": {...},
            "data_pegawai_ekinerja": {...}
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Profile retrieved successfully"
    }
    ```
    **Notes**:
-   The `meta` field is omitted as no pagination is used.

### GET /profile/apps

**Description**: Retrieve the authenticated user's app-specific data.
**Authentication**: Required (`api.auth`)
**Query Parameters**: None
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "user_absen": {
                "name": "string",
                ...
            },
            "user_ekinerja": {
                "UID": "string",
                ...
            }
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Apps data retrieved successfully"
    }
    ```

---

## 3. NewsController

### GET /news

**Description**: Retrieve a paginated list of news (Admin only).
**Authentication**: Required (`api.auth`, `admin`)
**Query Parameters**:

-   `page`: integer (default: 1)
-   `per_page`: integer (default: 20)
    **Response**:
-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": [
            {
                "id": "integer",
                "title": "string",
                "content": "string",
                "image_url": "string | null",
                "user": {
                    "id": "integer",
                    "username": "string",
                    "name": "string"
                },
                "created_at": "ISO 8601 timestamp",
                "updated_at": "ISO 8601 timestamp"
            },
            ...
        ],
        "error": null,
        "meta": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "last_page": 3,
            "from": 1,
            "to": 20
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "News list retrieved successfully"
    }
    ```

### GET /news/latest

**Description**: Retrieve the latest 5 news items.
**Authentication**: None
**Query Parameters**: None
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": [
            {
                "id": "integer",
                "title": "string",
                "content": "string",
                "image_url": "string | null",
                "user": {
                    "id": "integer",
                    "username": "string",
                    "name": "string"
                },
                "created_at": "ISO 8601 timestamp",
                "updated_at": "ISO 8601 timestamp"
            },
            ...
        ],
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Latest news retrieved successfully"
    }
    ```

### POST /news

**Description**: Create a new news item (Admin only).
**Authentication**: Required (`api.auth`, `admin`)
**Request Body**:

```json
{
    "title": "string",
    "content": "string",
    "image": "file (jpeg, png, jpg, max 2MB) | null"
}
```

**Response**:

-   **201 Created**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "image_url": "string | null",
            "user": {
                "id": "integer",
                "username": "string",
                "name": "string"
            },
            "created_at": "ISO 8601 timestamp",
            "updated_at": "ISO 8601 timestamp"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "News created successfully"
    }
    ```

### GET /news/{id}

**Description**: Retrieve a specific news item.
**Authentication**: None
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "image_url": "string | null",
            "user": {
                "id": "integer",
                "username": "string",
                "name": "string"
            },
            "created_at": "ISO 8601 timestamp",
            "updated_at": "ISO 8601 timestamp"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "News retrieved successfully"
    }
    ```

### PUT /news/{id}

**Description**: Update an existing news item (Admin only).
**Authentication**: Required (`api.auth`, `admin`)
**Request Body**:

```json
{
    "title": "string | optional",
    "content": "string | optional",
    "image": "file (jpeg, png, jpg, max 2MB) | optional"
}
```

**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            "title": "string",
            "content": "string",
            "image_url": "string | null",
            "user": {
                "id": "integer",
                "username": "string",
                "name": "string"
            },
            "created_at": "ISO 8601 timestamp",
            "updated_at": "ISO 8601 timestamp"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "News updated successfully"
    }
    ```

### DELETE /news/{id}

**Description**: Delete a news item (Admin only).
**Authentication**: Required (`api.auth`, `admin`)
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": null,
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "News deleted successfully"
    }
    ```

---

## 4. OfficeController

### GET /offices/me

**Description**: Retrieve authenticated user's office data.
**Authentication**: Required (`api.auth`)
**Query Parameters**: None
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "office_absen": {
                "id_instansi": "string",
                "nama_instansi": "string",
                "alamat_instansi": "string | null",
                "kota": "string | null",
                "kodepos": "string | null",
                "phone": "string | null",
                "fax": "string | null",
                "website": "string | null",
                "email": "string | null"
            },
            "office_simpeg": {...},
            "office_ekinerja": {...}
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Office data retrieved successfully"
    }
    ```

### GET /offices/{instansi_id}

**Description**: Retrieve office data by instansi ID.
**Authentication**: Required (`api.auth`)
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "office_absen": {...},
            "office_simpeg": {...},
            "office_ekinerja": {...}
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Office data retrieved successfully"
    }
    ```

### GET /offices/{instansi_id}/coordinates

**Description**: Retrieve coordinate data by instansi ID.
**Authentication**: Required (`api.auth`)
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "id_instansi": "string",
            "latitude": "string",
            "longitude": "string",
            "radius": "integer",
            "aktif": "integer"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Coordinate data retrieved successfully"
    }
    ```

---

## 5. AttendanceController

### GET /attendances/me

**Description**: Retrieve authenticated user's monthly attendance.
**Authentication**: Required (`api.auth`)
**Query Parameters**:

-   `page`: integer (default: 1)
-   `per_page`: integer (default: 20)
    **Response**:
-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "month": "string",
            "attendances": [
                {
                    "id": "integer",
                    "id_checkinout": "string",
                    "nip_pegawai": "string",
                    "id_instansi": "string",
                    "id_unit_kerja": "string",
                    "id_profile": "string",
                    "date": "date",
                    "checktime": "ISO 8601 timestamp",
                    "checktype": "string",
                    "iplog": "string",
                    "coordinate": "string",
                    "altitude": "numeric",
                    "jenis_absensi": "string",
                    "user_platform": "string",
                    "browser_name": "string",
                    "browser_version": "string",
                    "aprv_stats": "string",
                    "aprv_by": "string | null",
                    "aprv_on": "ISO 8601 timestamp | null",
                    "reject_by": "string | null",
                    "reject_on": "ISO 8601 timestamp | null",
                    "created_at": "ISO 8601 timestamp",
                    "updated_at": "ISO 8601 timestamp"
                },
                ...
            ]
        },
        "error": null,
        "meta": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "last_page": 3,
            "from": 1,
            "to": 20
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Monthly attendance retrieved successfully"
    }
    ```

### POST /attendances/check-in

**Description**: Record an automatic check-in.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "coordinate": "string",
    "altitude": "numeric",
    "checktype": "string",
    "jenis_absensi": "string"
}
```

**Response**:

-   **201 Created**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            "id_checkinout": "string",
            ...
            "aprv_stats": "Y",
            ...
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Check-in successful"
    }
    ```

### POST /attendances/manual-check-in

**Description**: Record a manual check-in.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "date": "date",
    "checktime": "Y-m-d H:i:s",
    "coordinate": "string",
    "altitude": "numeric",
    "checktype": "string",
    "jenis_absensi": "string"
}
```

**Response**:

-   **201 Created**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            "id_checkinout": "string",
            ...
            "aprv_stats": "N",
            ...
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Manual check-in successful"
    }
    ```

### POST /attendances/{id}/approve-or-reject

**Description**: Approve or reject a manual attendance record.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "status": "Y | N"
}
```

**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            ...
            "aprv_stats": "Y",
            "aprv_by": "string",
            "aprv_on": "ISO 8601 timestamp",
            ...
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Manual attendance approved successfully"
    }
    ```

### GET /attendances/manual

**Description**: List manual attendance records by approval status.
**Authentication**: Required (`api.auth`)
**Query Parameters**:

-   `status`: string (Y | N, required)
-   `page`: integer (default: 1)
-   `per_page`: integer (default: 20)
    **Response**:
-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": [
            {
                "id": "integer",
                ...
            },
            ...
        ],
        "error": null,
        "meta": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "last_page": 3,
            "from": 1,
            "to": 20
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Manual attendance list retrieved successfully"
    }
    ```

### POST /attendances/upload-photo

**Description**: Upload a photo for attendance.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "photo": "file (jpeg, png, jpg, max 2MB)",
    "checktype": "auto | manual"
}
```

**Response**:

-   **201 Created**:
    ```json
    {
        "status": "success",
        "data": {
            "file_path": "string"
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Photo uploaded successfully"
    }
    ```

---

## 6. PerformanceController

### GET /performances

**Description**: Retrieve all performances for the authenticated user.
**Authentication**: Required (`api.auth`)
**Query Parameters**:

-   `page`: integer (default: 1)
-   `per_page`: integer (default: 20)
    **Response**:
-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": [
            {
                "id": "integer",
                "nama": "string",
                "penjelasan": "string | null",
                "tglKinerja": "date",
                "durasiKinerjaMulai": "H:i",
                "durasiKinerjaSelesai": "H:i",
                "durasiKinerja": "H:I",
                "menitKinerja": "integer",
                "apv": "P | A | R",
                "tupoksi": "string | null",
                "periodeKinerja": "string | null",
                "target": "integer | null",
                "satuanTarget": "string | null",
                "NIP": "string",
                "created_at": "ISO 8601 timestamp",
                "updated_at": "ISO 8601 timestamp"
            },
            ...
        ],
        "error": null,
        "meta": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "last_page": 3,
            "from": 1,
            "to": 20
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Performance list retrieved successfully"
    }
    ```

### POST /performances

**Description**: Create a new performance record.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "nama": "string",
    "penjelasan": "string | null",
    "tglKinerja": "date",
    "durasiKinerjaMulai": "H:i",
    "durasiKinerjaSelesai": "H:i",
    "tupoksi": "string | null",
    "periodeKinerja": "string | null",
    "target": "integer | null",
    "satuanTarget": "string | null"
}
```

**Response**:

-   **201 Created**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            ...
            "apv": "P",
            ...
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Performance created successfully"
    }
    ```

### GET /performances/{id}

**Description**: Retrieve a specific performance record.
**Authentication**: Required (`api.auth`)
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            ...
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Performance retrieved successfully"
    }
    ```

### PUT /performances/{id}

**Description**: Update an existing performance record.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "nama": "string | optional",
    "penjelasan": "string | optional",
    "durasiKinerjaMulai": "H:i | optional",
    "durasiKinerjaSelesai": "H:i | optional",
    "tupoksi": "string | optional",
    "periodeKinerja": "string | optional",
    "target": "integer | optional",
    "satuanTarget": "string | optional"
}
```

**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "id": "integer",
            ...
        },
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Performance updated successfully"
    }
    ```

### DELETE /performances/{id}

**Description**: Soft delete a performance record.
**Authentication**: Required (`api.auth`)
**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": null,
        "error": null,
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Performance soft deleted successfully"
    }
    ```

### GET /performances/me

**Description**: Retrieve authenticated user's monthly performance.
**Authentication**: Required (`api.auth`)
**Query Parameters**:

-   `page`: integer (default: 1)
-   `per_page`: integer (default: 20)
    **Response**:
-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": {
            "month": "string",
            "performances": [
                {
                    "id": "integer",
                    ...
                },
                ...
            ]
        },
        "error": null,
        "meta": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "last_page": 3,
            "from": 1,
            "to": 20
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Monthly performance retrieved successfully"
    }
    ```

### POST /performances/filter

**Description**: Filter performances by approval status, month, and year.
**Authentication**: Required (`api.auth`)
**Request Body**:

```json
{
    "month": "integer (1-12)",
    "year": "integer (2000-2100)",
    "apvId": "P | A | R | null",
    "NIP": "string | null"
}
```

**Response**:

-   **200 OK**:
    ```json
    {
        "status": "success",
        "data": [
            {
                "id": "integer",
                ...
            },
            ...
        ],
        "error": null,
        "meta": {
            "current_page": 1,
            "per_page": 20,
            "total": 50,
            "last_page": 3,
            "from": 1,
            "to": 20
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": "Filtered performances retrieved successfully"
    }
    ```

---

## Error Responses

Common error responses include:

-   **401 Unauthorized**:
    ```json
    {
        "status": "error",
        "data": null,
        "error": {
            "code": 401,
            "message": "User not authenticated",
            "details": null
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": null
    }
    ```
-   **404 Not Found**:
    ```json
    {
        "status": "error",
        "data": null,
        "error": {
            "code": 404,
            "message": "Resource not found",
            "details": null
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": null
    }
    ```
-   **422 Unprocessable Entity** (validation error):
    ```json
    {
        "status": "error",
        "data": null,
        "error": {
            "code": 422,
            "message": "Invalid input",
            "details": {
                "field": ["Error message"]
            }
        },
        "last_updated": "2025-04-22T03:18:36+00:00",
        "message": null
    }
    ```

## Notes

-   All endpoints requiring authentication expect the `Authorization: Bearer <token>` header.
-   Pagination-enabled endpoints (`GET /news`, `GET /attendances/me`, `GET /attendances/manual`, `GET /performances`, `GET /performances/me`, `POST /performances/filter`) include the `meta` field with pagination details.
-   File uploads (`POST /news`, `POST /attendances/upload-photo`) require multipart/form-data requests.
-   Rate limiting applies to `/auth/login` with a limit of 5 attempts per minute per username + device ID.
-   Soft deletes are used in `/news` and `/performances` endpoints, excluding deleted records from responses.
