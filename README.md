# Employee Management System API

## **Backend Overview**

This backend is built with Laravel and provides a comprehensive employee management system with attendance tracking, performance management, authentication, and various administrative features.

### **Core Features**

## 1. **Authentication & Authorization System**

- **Multi-token Authentication**: Supports both access tokens and refresh tokens
- **Device-based Security**: Requires `X-Device-ID` header for enhanced security
- **Token Management**: Login, logout, token refresh, and validation endpoints
- **Password Management**: Secure password change functionality
- **Admin Role Protection**: Admin-only routes protected by `AdminMiddleware`
- **Rate Limiting**: Login attempts limited to prevent brute force attacks

## 2. **User Profile Management**

- **Profile Information**: Access to user profile data and application permissions
- **Multi-database Support**: Integrates with both main and e-kinerja databases
- **Office Information**: User's office and institutional data access

## 3. **Attendance Management System**

### **Real-time Attendance**
- **Auto Check-in**: GPS-based attendance with coordinate validation
- **Manual Check-in**: Historical attendance submission with approval workflow
- **Photo Documentation**: Upload attendance photos to private/public storage
- **Selfie Metadata**: Stores photo metadata in `vd_data_selfie` table

### **Attendance Features**
- **Approval Workflow**: Manual check-ins require supervisor approval
- **Monthly Reports**: Paginated monthly attendance records with caching
- **Coordinate Tracking**: GPS location and altitude recording
- **Multiple Check Types**: Support for different attendance types (in/out office, incidental)

## 4. **Performance Management System**

### **Performance Tracking**
- **CRUD Operations**: Complete performance record management
- **Duration Calculation**: Auto-calculates work duration from start/end times
- **Approval Workflow**: Three-tier approval system (Pending/Approved/Rejected)
- **Approver Assignment**: Auto-assigns approver ID from user hierarchy

### **Performance Features**
- **Soft Delete**: Records marked as deleted instead of permanent removal
- **Filtering System**: Filter by month, year, approval status, and employee
- **Validation Rules**: Ensures data integrity and business logic compliance
- **Caching**: Monthly performance data cached for improved performance
- **Update Restrictions**: Prevents modification of approved/rejected records

## 5. **Calendar & Holiday Management**

- **Holiday Checking**: Verify if specific dates are holidays for institutions
- **Incidental Days**: Track ceremony days and special events
- **Institution-specific**: Holiday rules based on organizational units
- **Multi-status Support**: Different calendar event types (LIBUR, UPACARA)

## 6. **Application Letter System**

- **Leave Approval**: Check approved leave applications for specific dates
- **Monthly Reports**: Current month application letter summaries
- **Status Tracking**: Track application approval/rejection workflow
- **Employee-specific**: Filter by employee NIP or authenticated user

## 7. **Face Recognition System**

- **Face Model Management**: Upload and manage facial recognition models
- **Active Model Selection**: Set primary face model for verification
- **Verification Endpoint**: Face model verification for attendance
- **User-specific Models**: Multiple face models per user support
- **Rate Limited**: Protected endpoints with request throttling

## 8. **News & Communication**

- **Public News**: Latest news accessible without authentication
- **Admin Management**: Full CRUD operations for news management
- **Content Management**: Rich news content with metadata

## 9. **Office & Location Services**

- **Office Information**: Detailed office and institutional data
- **Coordinate Services**: GPS coordinates for office locations
- **Multi-level Organization**: Support for instansi, unit, and sub-unit structure

## 10. **Administrative Features**

### **User Management (Admin Only)**
- **User CRUD**: Complete user account management
- **Role Assignment**: Admin role and permission management
- **Account Control**: User activation/deactivation

### **System Management**
- **Version Control**: Application version checking
- **Cache Management**: Performance optimization through caching
- **Logging**: Comprehensive request and error logging

## 11. **Data Security & Validation**

- **Input Validation**: Comprehensive validation rules for all endpoints
- **SQL Injection Protection**: Parameterized queries and ORM usage
- **File Upload Security**: Secure file handling with type and size validation
- **Rate Limiting**: API abuse prevention
- **Error Handling**: Standardized error responses with detailed logging

---

### **API Endpoints Overview**

## **Authentication Endpoints**
- `POST /api/auth/login` - User authentication with token generation
- `POST /api/auth/login-with-data` - Login with additional user data
- `POST /api/auth/refresh-token` - Refresh access tokens
- `POST /api/auth/refresh-with-data` - Refresh with additional data
- `GET /api/auth/validate-token` - Token validation
- `POST /api/auth/logout` - User logout and token invalidation
- `POST /api/auth/change-password` - Password change functionality

## **Profile & User Management**
- `GET /api/profile/me` - User profile information
- `GET /api/profile/apps` - User application permissions
- `GET /api/users` - User management (Admin only)
- `POST /api/users` - Create users (Admin only)
- `PUT /api/users/{id}` - Update users (Admin only)
- `DELETE /api/users/{id}` - Delete users (Admin only)

## **Attendance Management**
- `GET /api/attendances/me` - Monthly attendance records
- `POST /api/attendances/check-in` - Real-time attendance check-in
- `POST /api/attendances/manual-check-in` - Historical attendance submission
- `GET /api/attendances/manual` - List manual attendance requests
- `POST /api/attendances/{id}/approve-or-reject` - Approve/reject manual attendance
- `POST /api/attendances/upload-photo` - Upload attendance photos (private)
- `POST /api/attendances/upload-photo-public` - Upload photos with metadata

## **Performance Management**
- `GET /api/performances` - List user performance records
- `POST /api/performances/filter` - Filter performances by criteria
- `POST /api/performances` - Create new performance record
- `GET /api/performances/{id}` - Get specific performance record
- `PUT /api/performances/{id}` - Update performance record
- `DELETE /api/performances/{id}` - Soft delete performance record

## **Calendar & Holiday Services**
- `GET /api/calendar/holidays` - Check holiday status for dates
- `GET /api/calendar/incidental-days` - Check incidental/ceremony days

## **Application Letter Services**
- `GET /api/application-letters` - List user application letters
- `POST /api/application-letters` - Create new application letter
- `GET /api/application-letters/{id}` - Get specific application letter
- `PUT /api/application-letters/{id}` - Update application letter
- `DELETE /api/application-letters/{id}` - Delete application letter
- `GET /api/application-letters/check-approval` - Check leave approvals
- `GET /api/application-letters/current-month` - Monthly application summary

## **Face Recognition Services**
- `GET /api/face-models` - List user face models
- `POST /api/face-models` - Upload new face model
- `GET /api/face-models/active` - Get active face model
- `PUT /api/face-models/{id}/set-active` - Set active face model
- `DELETE /api/face-models/{id}` - Delete face model
- `GET /api/face-models/verify` - Face verification endpoint

## **Office & Location Services**
- `GET /api/offices/me` - User's office information
- `GET /api/offices/{instansi_id}` - Office details by institution
- `GET /api/offices/{instansi_id}/coordinates` - Office GPS coordinates

## **News & Communication**
- `GET /api/news/latest` - Latest public news
- `GET /api/news/{id}` - Specific news article
- `GET /api/news` - All news (Admin only)
- `POST /api/news` - Create news (Admin only)
- `PUT /api/news/{id}` - Update news (Admin only)
- `DELETE /api/news/{id}` - Delete news (Admin only)

## **System Services**
- `GET /api/version/check` - Application version checking

---

### **Middleware**

1. **`api.auth`**:
   - Ensures the user is authenticated using Laravel Sanctum.

2. **`AdminMiddleware`**:
   - Restricts access to admin-only routes.
   - Returns a `403 Unauthorized` response if the user is not an admin.

---

### **Key Database Tables**

## **Performance Table (kinerja)**
| Field                  | Type      | Description                                     |
| ---------------------- | --------- | ----------------------------------------------- |
| `id`                   | `integer` | Primary key                                     |
| `nama`                 | `string`  | Performance task name                           |
| `penjelasan`           | `string`  | Task description                                |
| `tglKinerja`           | `date`    | Performance date                                |
| `durasiKinerjaMulai`   | `time`    | Start time                                      |
| `durasiKinerjaSelesai` | `time`    | End time                                        |
| `durasiKinerja`        | `string`  | Auto-calculated duration (HH:MM)                |
| `menitKinerja`         | `integer` | Duration in minutes                             |
| `apv`                  | `string`  | Approval status (P/A/R)                         |
| `apvId`                | `string`  | Approver ID from user hierarchy                 |
| `tupoksi`              | `string`  | Task responsibility                             |
| `periodeKinerja`       | `string`  | Performance period                              |
| `target`               | `integer` | Target value                                    |
| `satuanTarget`         | `string`  | Target unit                                     |
| `NIP`                  | `string`  | Employee identifier                             |
| `stsDel`               | `boolean` | Soft delete flag (0=active, 1=deleted)         |

## **Attendance Table**
| Field                  | Type      | Description                                     |
| ---------------------- | --------- | ----------------------------------------------- |
| `id`                   | `integer` | Primary key                                     |
| `id_checkinout`        | `string`  | Unique check-in identifier                      |
| `nip_pegawai`          | `string`  | Employee NIP                                    |
| `date`                 | `date`    | Attendance date                                 |
| `checktime`            | `datetime`| Check-in timestamp                              |
| `checktype`            | `string`  | Check type (auto/manual)                        |
| `coordinate`           | `string`  | GPS coordinates                                 |
| `altitude`             | `numeric` | GPS altitude                                    |
| `jenis_absensi`        | `string`  | Attendance type                                 |
| `aprv_stats`           | `string`  | Approval status (Y/N)                           |

## **Selfie Data Table (vd_data_selfie)**
| Field                  | Type      | Description                                     |
| ---------------------- | --------- | ----------------------------------------------- |
| `id_data_selfie`       | `string`  | Primary key                                     |
| `nip`                  | `string`  | Employee NIP                                    |
| `nama_file`            | `string`  | Photo filename                                  |
| `tgl_selfie`           | `datetime`| Photo timestamp                                 |
| `checktype`            | `string`  | Check type (I=In, O=Out)                        |
| `jenis_absensi`        | `integer` | Attendance type (1=Office, 2=Field, 3=Incidental) |

---

### **Technical Architecture**

## **Database Connections**
- **Primary Database**: Main application data (users, attendance, etc.)
- **E-Kinerja Database**: Performance and user hierarchy data
- **Multi-database Models**: Seamless integration across databases

## **Security Features**
- **Laravel Sanctum**: Token-based authentication
- **Middleware Protection**: Route-level security
- **Input Validation**: Comprehensive request validation
- **Rate Limiting**: API abuse prevention
- **File Security**: Secure file upload handling

## **Performance Optimizations**
- **Query Caching**: Monthly data caching (5-minute duration)
- **Eager Loading**: Optimized database queries
- **Pagination**: Efficient data loading
- **Database Indexing**: Optimized query performance

## **File Management**
- **Private Storage**: Secure file storage for sensitive documents
- **Public Storage**: Accessible file storage with UUID naming
- **Multiple Formats**: Support for various file types
- **Size Validation**: File size and type restrictions

## **API Standards**
- **RESTful Design**: Standard HTTP methods and status codes
- **JSON Responses**: Consistent response format
- **Error Handling**: Standardized error responses
- **Documentation**: Comprehensive API documentation

---

### **Setup Instructions**

## **1. Environment Setup**

```bash
# Clone the repository
git clone <repository-url>
cd <repository-folder>

# Install PHP dependencies
composer install

# Copy environment configuration
cp .env.example .env
```

## **2. Database Configuration**

Configure multiple database connections in `.env`:

```env
# Primary Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=main_database
DB_USERNAME=username
DB_PASSWORD=password

# E-Kinerja Database
DB_EKIN_CONNECTION=mysql_ekin
DB_EKIN_HOST=127.0.0.1
DB_EKIN_PORT=3306
DB_EKIN_DATABASE=ekinerja_database
DB_EKIN_USERNAME=username
DB_EKIN_PASSWORD=password
```

## **3. Application Setup**

```bash
# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Create storage symbolic link
php artisan storage:link

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
```

## **4. File Permissions**

```bash
# Set proper permissions
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## **5. Production Deployment**

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

## **6. Queue Configuration (Optional)**

```bash
# For background job processing
php artisan queue:work
```

---

### **Development Notes**

- Admin routes protected by `AdminMiddleware`
- Performance filtering supports multiple criteria
- Soft delete implementation across critical tables
- Comprehensive logging for debugging and monitoring
- Multi-timezone support (Asia/Makassar)
- Automatic duration calculations for performance records

---

### **API Documentation**

Detailed API documentation is available in `/docs/guidelines/API_V1.md` with:
- Complete endpoint specifications
- Request/response examples
- Authentication requirements
- Error handling guidelines
- Rate limiting information

### **System Requirements**

- **PHP**: 8.1 or higher
- **Laravel**: 10.x
- **MySQL**: 5.7 or higher
- **Extensions**: PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON
- **Memory**: Minimum 512MB RAM
- **Storage**: Adequate space for file uploads and logs

### **Support & Maintenance**

- **Logging**: Comprehensive application and error logging
- **Monitoring**: Performance and security monitoring
- **Backup**: Regular database and file backups recommended
- **Updates**: Regular security and feature updates