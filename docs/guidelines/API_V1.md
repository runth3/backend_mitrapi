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
**Description**: Get authenticated user's monthly attendance records with pagination.
**Authentication**: Required (api.auth)
**Query Parameters**: 
- `per_page` (optional): Items per page (default: 20)

**Response**:
```json
{
  "status": "success",
  "data": {
    "month": "2025-01",
    "attendances": [
      {
        "id": 1,
        "id_checkinout": "ABC123DEF456",
        "nip_pegawai": "199703062020122015",
        "date": "2025-01-15",
        "checktime": "2025-01-15 08:30:00",
        "checktype": "auto",
        "jenis_absensi": "masuk",
        "coordinate": "-6.200000,106.816666",
        "altitude": 25.5,
        "aprv_stats": "Y"
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2
  },
  "message": "Monthly attendance retrieved successfully"
}
```

### POST /attendances/check-in
**Description**: Check in attendance for current date and time.
**Authentication**: Required (api.auth)
**Request Body**:
```json
{
  "coordinate": "-6.200000,106.816666",
  "altitude": 25.5,
  "checktype": "auto",
  "jenis_absensi": "masuk"
}
```
**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "id_checkinout": "ABC123DEF456",
    "nip_pegawai": "199703062020122015",
    "date": "2025-01-15",
    "checktime": "2025-01-15 08:30:00",
    "checktype": "auto",
    "jenis_absensi": "masuk",
    "coordinate": "-6.200000,106.816666",
    "altitude": 25.5,
    "aprv_stats": "Y"
  },
  "message": "Check-in successful"
}
```

### POST /attendances/manual-check-in
**Description**: Submit manual check-in request for past date (requires approval).
**Authentication**: Required (api.auth)
**Request Body**:
```json
{
  "date": "2025-01-14",
  "checktime": "2025-01-14 08:30:00",
  "coordinate": "-6.200000,106.816666",
  "altitude": 25.5,
  "checktype": "manual",
  "jenis_absensi": "masuk"
}
```
**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 2,
    "id_checkinout": "XYZ789ABC123",
    "nip_pegawai": "199703062020122015",
    "date": "2025-01-14",
    "checktime": "2025-01-14 08:30:00",
    "checktype": "manual",
    "jenis_absensi": "masuk",
    "coordinate": "-6.200000,106.816666",
    "altitude": 25.5,
    "aprv_stats": "N"
  },
  "message": "Manual check-in successful"
}
```

### GET /attendances/manual
**Description**: List manual attendance requests by approval status.
**Authentication**: Required (api.auth)
**Query Parameters**: 
- `status` (required): Approval status (Y=approved, N=pending)
- `per_page` (optional): Items per page (default: 20)

**Example Request**: `GET /api/attendances/manual?status=N&per_page=10`

**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 2,
      "id_checkinout": "XYZ789ABC123",
      "nip_pegawai": "199703062020122015",
      "date": "2025-01-14",
      "checktime": "2025-01-14 08:30:00",
      "checktype": "manual",
      "jenis_absensi": "masuk",
      "aprv_stats": "N"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 5,
    "last_page": 1
  },
  "message": "Manual attendance list retrieved"
}
```

### POST /attendances/{id}/approve-or-reject
**Description**: Approve or reject manual attendance request.
**Authentication**: Required (api.auth)
**Request Body**:
```json
{
  "status": "Y"
}
```
**Response (Approved)**:
```json
{
  "status": "success",
  "data": {
    "id": 2,
    "id_checkinout": "XYZ789ABC123",
    "nip_pegawai": "199703062020122015",
    "date": "2025-01-14",
    "checktime": "2025-01-14 08:30:00",
    "checktype": "manual",
    "aprv_stats": "Y",
    "aprv_by": "admin",
    "aprv_on": "2025-01-15 10:30:00"
  },
  "message": "Manual attendance approved successfully"
}
```

### POST /attendances/upload-photo
**Description**: Upload attendance photo to private storage.
**Authentication**: Required (api.auth)
**Request Body**: 
```json
{
  "photo": "file (required: image, max 2MB)",
  "checktype": "string (required: auto|manual)"
}
```
**Response**:
```json
{
  "status": "success",
  "data": {
    "file_path": "absen/2025-01-15/199703062020122015-A-1737000000.jpg"
  },
  "message": "Photo uploaded successfully"
}
```

### POST /attendances/upload-photo-public
**Description**: Upload attendance photo to public storage and save metadata to vd_data_selfie table.
**Authentication**: Required (api.auth)
**Request Body**: 
```json
{
  "photo": "file (required: image, max 2MB)",
  "checktype": "string (required: I=In, O=Out)",
  "jenis_absensi": "integer (required: 1=Dalam Kantor, 2=Luar Kantor, 3=Insidental)"
}
```
**Response**:
```json
{
  "status": "success",
  "data": {
    "id_data_selfie": "5A0OHEHVIA8S8",
    "file_path": "attendance/2025/01/15/199703062020122015_20250115102638.jpg",
    "public_url": "https://example.com/storage/attendance/2025/01/15/199703062020122015_20250115102638.jpg",
    "nama_file": "199703062020122015_20250115102638.jpg"
  },
  "message": "Photo uploaded and metadata saved successfully"
}
```

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
**Description**: Get all performance records for authenticated user (excluding soft-deleted records).
**Authentication**: Required (api.auth)
**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nama": "Laporan Bulanan",
      "penjelasan": "Membuat laporan kinerja bulanan",
      "tglKinerja": "2025-01-15",
      "durasiKinerjaMulai": "08:00:00",
      "durasiKinerjaSelesai": "10:00:00",
      "durasiKinerja": "02:00",
      "menitKinerja": 120,
      "apv": "P",
      "apvId": "USR001",
      "tupoksi": "Administrasi",
      "periodeKinerja": "January 2025",
      "target": 1,
      "satuanTarget": "laporan",
      "NIP": "199703062020122015",
      "stsDel": 0
    }
  ],
  "message": "Performance records retrieved successfully"
}
```

### POST /performances/filter
**Description**: Filter performance records by month, year, approval status, and optionally NIP.
**Authentication**: Required (api.auth)
**Request Body**: 
```json
{
  "month": 1,
  "year": 2025,
  "apv": "P",
  "NIP": "199703062020122015"
}
```
**Response**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nama": "Laporan Bulanan",
      "penjelasan": "Membuat laporan kinerja bulanan",
      "tglKinerja": "2025-01-15",
      "durasiKinerjaMulai": "08:00:00",
      "durasiKinerjaSelesai": "10:00:00",
      "durasiKinerja": "02:00",
      "menitKinerja": 120,
      "apv": "P",
      "apvId": "USR001",
      "tupoksi": "Administrasi",
      "periodeKinerja": "January 2025",
      "target": 1,
      "satuanTarget": "laporan",
      "NIP": "199703062020122015",
      "stsDel": 0
    }
  ],
  "message": "Filtered performance records retrieved successfully"
}
```

### POST /performances
**Description**: Create new performance record with default approval status 'P' (Pending).
**Authentication**: Required (api.auth)
**Request Body**:
```json
{
  "nama": "Laporan Bulanan",
  "penjelasan": "Membuat laporan kinerja bulanan",
  "tglKinerja": "2025-01-15",
  "durasiKinerjaMulai": "08:00:00",
  "durasiKinerjaSelesai": "10:00:00",
  "tupoksi": "Administrasi",
  "target": 1,
  "satuanTarget": "laporan"
}
```
**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "nama": "Laporan Bulanan",
    "penjelasan": "Membuat laporan kinerja bulanan",
    "tglKinerja": "2025-01-15",
    "durasiKinerjaMulai": "08:00:00",
    "durasiKinerjaSelesai": "10:00:00",
    "durasiKinerja": "02:00",
    "menitKinerja": 120,
    "apv": "P",
    "tupoksi": "Administrasi",
    "periodeKinerja": "January 2025",
    "target": 1,
    "satuanTarget": "laporan",
    "NIP": "199703062020122015",
    "stsDel": 0,
    "created_at": "2025-01-15T08:30:00.000000Z",
    "updated_at": "2025-01-15T08:30:00.000000Z"
  },
  "message": "Performance record created successfully"
}
```

### GET /performances/{id}
**Description**: Get specific performance record by ID.
**Authentication**: Required (api.auth)
**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "nama": "Laporan Bulanan",
    "penjelasan": "Membuat laporan kinerja bulanan",
    "tglKinerja": "2025-01-15",
    "durasiKinerjaMulai": "08:00:00",
    "durasiKinerjaSelesai": "10:00:00",
    "durasiKinerja": "02:00",
    "menitKinerja": 120,
    "apv": "P",
    "apvId": "USR001",
    "tupoksi": "Administrasi",
    "periodeKinerja": "January 2025",
    "target": 1,
    "satuanTarget": "laporan",
    "NIP": "199703062020122015",
    "stsDel": 0,
    "created_at": "2025-01-15T08:30:00.000000Z",
    "updated_at": "2025-01-15T08:30:00.000000Z"
  },
  "message": "Performance record retrieved successfully"
}
```

### PUT /performances/{id}
**Description**: Update performance record (restricted if approval status is 'A' or 'R').
**Authentication**: Required (api.auth)
**Request Body**:
```json
{
  "nama": "Laporan Bulanan Updated",
  "penjelasan": "Membuat dan merevisi laporan kinerja bulanan",
  "target": 2
}
```
**Response (Success)**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "nama": "Laporan Bulanan Updated",
    "penjelasan": "Membuat dan merevisi laporan kinerja bulanan",
    "tglKinerja": "2025-01-15",
    "durasiKinerjaMulai": "08:00:00",
    "durasiKinerjaSelesai": "10:00:00",
    "durasiKinerja": "02:00",
    "menitKinerja": 120,
    "apv": "P",
    "apvId": "USR001",
    "tupoksi": "Administrasi",
    "periodeKinerja": "January 2025",
    "target": 2,
    "satuanTarget": "laporan",
    "NIP": "199703062020122015",
    "stsDel": 0,
    "updated_at": "2025-01-15T09:30:00.000000Z"
  },
  "message": "Performance record updated successfully"
}
```
**Response (Restricted)**:
```json
{
  "status": "error",
  "error": {
    "code": 400,
    "message": "Cannot update performance record with approved or rejected status",
    "details": {}
  },
  "message": "Update not allowed"
}
```

### DELETE /performances/{id}
**Description**: Soft delete performance record by setting stsDel = 1.
**Authentication**: Required (api.auth)
**Response**:
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "nama": "Laporan Bulanan",
    "stsDel": 1,
    "deleted_at": "2025-01-15T10:30:00.000000Z"
  },
  "message": "Performance record deleted successfully"
}
```

**Performance Fields**:
- `nama`: Performance task name
- `penjelasan`: Task description  
- `tglKinerja`: Performance date
- `durasiKinerjaMulai`: Start time
- `durasiKinerjaSelesai`: End time
- `durasiKinerja`: Auto-calculated duration (HH:MM format)
- `menitKinerja`: Auto-calculated duration in minutes
- `apv`: Approval status (P=Pending, A=Approved, R=Rejected)
- `apvId`: Approver ID (auto-filled from UserEkinerja during creation)
- `tupoksi`: Task responsibility
- `periodeKinerja`: Auto-generated performance period
- `target`: Target value
- `satuanTarget`: Target unit
- `NIP`: User identifier (auto-filled)
- `stsDel`: Soft delete flag (0=active, 1=deleted)

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
      "jenis_permohonan": "TL",
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