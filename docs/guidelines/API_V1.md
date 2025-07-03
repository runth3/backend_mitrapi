# API Documentation v1

## Base URL
`/api`

## Authentication
- **Bearer Token**: Required for authenticated endpoints
- **X-Device-ID Header**: Required for all auth-related endpoints

---

## 1. AuthController

### POST /auth/login
**Description**: Authenticate a user and issue an access token along with a refresh token.
**Authentication**: None
**Headers**: `X-Device-ID` (required)
**Request Body**: `{"username": "string", "password": "string"}`

### POST /auth/login-with-data
**Description**: Authenticate user and return access token with additional data.
**Authentication**: None
**Headers**: `X-Device-ID` (required)
**Request Body**: `{"username": "string", "password": "string", "device_id": "string"}`

### POST /auth/refresh-token
**Description**: Refresh an access token using a valid refresh token.
**Authentication**: None
**Headers**: `X-Device-ID` (required)
**Request Body**: `{"refresh_token": "string"}`

### POST /auth/refresh-with-data
**Description**: Refresh token and return new token with additional data.
**Authentication**: None
**Headers**: `X-Device-ID` (required)
**Request Body**: `{"refresh_token": "string", "device_id": "string"}`

### GET /auth/validate-token
**Description**: Validate an access token.
**Authentication**: Required (api.auth)
**Headers**: `Authorization: Bearer <token>`, `X-Device-ID` (required)

### POST /auth/logout
**Description**: Invalidate the current access token and refresh token.
**Authentication**: Required (api.auth)
**Headers**: `X-Device-ID` (required)

### POST /auth/change-password
**Description**: Change the authenticated user's password.
**Authentication**: Required (api.auth)
**Request Body**: `{"current_password": "string", "new_password": "string", "new_password_confirmation": "string"}`

---

## 2. ProfileController

### GET /profile/me
**Description**: Get authenticated user's profile information.
**Authentication**: Required (api.auth)

### GET /profile/apps
**Description**: Get user's application access information.
**Authentication**: Required (api.auth)

---

## 3. NewsController

### GET /news/latest
**Description**: Get latest news (public endpoint).
**Authentication**: None

### GET /news/{id}
**Description**: Get specific news by ID (public endpoint).
**Authentication**: None

### GET /news (Admin)
**Description**: Get all news (admin only).
**Authentication**: Required (api.auth + admin)

### POST /news (Admin)
**Description**: Create new news (admin only).
**Authentication**: Required (api.auth + admin)

### PUT /news/{id} (Admin)
**Description**: Update news (admin only).
**Authentication**: Required (api.auth + admin)

### DELETE /news/{id} (Admin)
**Description**: Delete news (admin only).
**Authentication**: Required (api.auth + admin)

---

## 4. OfficeController

### GET /offices/me
**Description**: Get authenticated user's office information.
**Authentication**: Required (api.auth)

### GET /offices/{instansi_id}
**Description**: Get office information by instansi ID.
**Authentication**: Required (api.auth)

### GET /offices/{instansi_id}/coordinates
**Description**: Get office coordinates by instansi ID.
**Authentication**: Required (api.auth)

---

## 5. AttendanceController

### GET /attendances/me
**Description**: Get authenticated user's attendance records.
**Authentication**: Required (api.auth)

### POST /attendances/check-in
**Description**: Check in attendance.
**Authentication**: Required (api.auth)

### POST /attendances/manual-check-in
**Description**: Submit manual check-in request.
**Authentication**: Required (api.auth)

### GET /attendances/manual
**Description**: List manual attendance requests.
**Authentication**: Required (api.auth)

### POST /attendances/{id}/approve-or-reject
**Description**: Approve or reject attendance request.
**Authentication**: Required (api.auth)

### POST /attendances/upload-photo
**Description**: Upload attendance photo.
**Authentication**: Required (api.auth)

### GET /attendances (Admin)
**Description**: Get all attendance records (admin only).
**Authentication**: Required (api.auth + admin)

### GET /attendances/{id} (Admin)
**Description**: Get specific attendance record (admin only).
**Authentication**: Required (api.auth + admin)

### PUT /attendances/{id} (Admin)
**Description**: Update attendance record (admin only).
**Authentication**: Required (api.auth + admin)

### DELETE /attendances/{id} (Admin)
**Description**: Delete attendance record (admin only).
**Authentication**: Required (api.auth + admin)

---

## 6. PerformanceController

### GET /performances
**Description**: Get all performance records for authenticated user.
**Authentication**: Required (api.auth)

### GET /performances/me
**Description**: Get authenticated user's performance records.
**Authentication**: Required (api.auth)

### POST /performances/filter
**Description**: Filter performance records by approval status.
**Authentication**: Required (api.auth)

### POST /performances
**Description**: Create new performance record.
**Authentication**: Required (api.auth)

### GET /performances/{id}
**Description**: Get specific performance record.
**Authentication**: Required (api.auth)

### PUT /performances/{id}
**Description**: Update performance record.
**Authentication**: Required (api.auth)

### DELETE /performances/{id}
**Description**: Delete performance record (soft delete).
**Authentication**: Required (api.auth)

---

## 7. FaceModelController

### GET /face-models
**Description**: Get user's face models.
**Authentication**: Required (api.auth)

### POST /face-models
**Description**: Create/upload new face model.
**Authentication**: Required (api.auth)

### GET /face-models/active
**Description**: Get active face model for user.
**Authentication**: Required (api.auth)
**Rate Limit**: 60 requests per minute

### GET /face-models/{id}
**Description**: Get specific face model.
**Authentication**: Required (api.auth)
**Rate Limit**: 60 requests per minute

### PUT /face-models/{id}/set-active
**Description**: Set face model as active.
**Authentication**: Required (api.auth)

### DELETE /face-models/{id}
**Description**: Delete face model.
**Authentication**: Required (api.auth)

### GET /face-models/user/{user_id}
**Description**: Get face models by user ID.
**Authentication**: Required (api.auth)

### GET /face-models/verify
**Description**: Get active face model for verification.
**Authentication**: Required (api.auth)
**Rate Limit**: 60 requests per minute

---

## 8. UserController (Admin Only)

### GET /users
**Description**: Get all users (admin only).
**Authentication**: Required (api.auth + admin)

### POST /users
**Description**: Create new user (admin only).
**Authentication**: Required (api.auth + admin)

### GET /users/{id}
**Description**: Get specific user (admin only).
**Authentication**: Required (api.auth + admin)

### PUT /users/{id}
**Description**: Update user (admin only).
**Authentication**: Required (api.auth + admin)

### DELETE /users/{id}
**Description**: Delete user (admin only).
**Authentication**: Required (api.auth + admin)

---

## 9. CalendarController

### GET /calendar/holidays
**Description**: Check if a specific date is a holiday for the user's instansi.
**Authentication**: Required (api.auth)
**Query Parameters**: 
- `date` (required): Date to check (YYYY-MM-DD format)
- `id_instansi` (required): Institution ID

**Example Request**: `GET /api/calendar/holidays?date=2025-01-01&id_instansi=3L99WJ2WPQ0W0`

**Response**:
```json
{
  "status": "success",
  "data": {
    "date": "2025-01-01",
    "is_holiday": true,
    "holiday_info": {
      "id": "HOLIDAY001",
      "title": "New Year Holiday",
      "start_date": "2025-01-01",
      "end_date": "2025-01-01",
      "status": "LIBUR",
      "color": "#FF0000"
    }
  },
  "message": "Date is a holiday"
}
```

### GET /calendar/incidental-days
**Description**: Check if a specific date is an incidental day (ceremony/upacara) for the user's instansi.
**Authentication**: Required (api.auth)
**Query Parameters**: 
- `date` (required): Date to check (YYYY-MM-DD format)
- `id_instansi` (required): Institution ID

**Example Request**: `GET /api/calendar/incidental-days?date=2025-08-17&id_instansi=3L99WJ2WPQ0W0`

**Response**:
```json
{
  "status": "success",
  "data": {
    "date": "2025-08-17",
    "is_incidental_day": true,
    "incidental_info": {
      "id": "UPACARA001",
      "title": "Independence Day Ceremony",
      "start_date": "2025-08-17",
      "end_date": "2025-08-17",
      "start_time": "07:00:00",
      "end_time": "09:00:00",
      "status": "UPACARA",
      "color": "#00FF00"
    }
  },
  "message": "Date is an incidental day"
}
```

---

## 10. ApplicationLetterController

### GET /application-letters/check-approval
**Description**: Check if there's an approved application letter for a specific date and employee.
**Authentication**: Required (api.auth)
**Query Parameters**: 
- `date` (required): Date to check (YYYY-MM-DD format)
- `nip_pegawai` (optional): Employee NIP (defaults to authenticated user's username)

**Example Request**: `GET /api/application-letters/check-approval?date=2025-01-15&nip_pegawai=199703062020122015`

**Response (Has Approval)**:
```json
{
  "status": "success",
  "data": {
    "date": "2025-01-15",
    "nip_pegawai": "199703062020122015",
    "has_approval": true,
    "application_info": {
      "id": "APP001",
      "jenis_permohonan": "01",
      "start_date": "2025-01-15",
      "end_date": "2025-01-15",
      "description": "Sick leave",
      "approved_by": "admin",
      "approved_on": "2025-01-10 10:30:00"
    }
  },
  "message": "Application letter found for this date"
}
```

**Response (No Approval)**:
```json
{
  "status": "success",
  "data": {
    "date": "2025-01-15",
    "nip_pegawai": "199703062020122015",
    "has_approval": false,
    "application_info": null
  },
  "message": "No approved application letter found for this date"
}
```

### GET /application-letters/current-month
**Description**: Get all application letters for the current month by employee NIP.
**Authentication**: Required (api.auth)
**Query Parameters**: 
- `nip_pegawai` (optional): Employee NIP (defaults to authenticated user's username)

**Example Request**: `GET /api/application-letters/current-month?nip_pegawai=199703062020122015`

**Response**:
```json
{
  "status": "success",
  "data": {
    "month": "2025-01",
    "nip_pegawai": "199703062020122015",
    "applications": [
      {
        "id": "APP001",
        "jenis_permohonan": "01",
        "start_date": "2025-01-15",
        "end_date": "2025-01-15",
        "description": "Sick leave",
        "status": 2,
        "status_text": "Approved",
        "created_on": "2025-01-10 09:00:00",
        "approved_by": "admin",
        "approved_on": "2025-01-10 10:30:00",
        "rejected_by": null,
        "rejected_on": null,
        "reason": null
      }
    ]
  },
  "message": "Application letters retrieved successfully"
}
```

---

## 11. VersionController

### GET /version/check
**Description**: Check application version.
**Authentication**: Required (api.auth)

---

## Rate Limiting

- **Login endpoints**: 5 attempts per minute per username + device ID
- **Face model endpoints**: 60 requests per minute
- **General API**: Standard Laravel rate limiting applies

## Error Response Format

```json
{
    "status": "error",
    "data": null,
    "error": {
        "code": 400,
        "message": "Error message",
        "details": {}
    },
    "last_updated": "2025-01-01T00:00:00+00:00",
    "message": "Error message"
}
```

## Success Response Format

```json
{
    "status": "success",
    "data": {},
    "error": null,
    "last_updated": "2025-01-01T00:00:00+00:00",
    "message": "Success message"
}
```