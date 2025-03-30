Backend Overview
This backend is built with Laravel and provides CRUD functionality for managing performances, with additional features like soft delete, filtering, and admin restrictions.

Features
Authentication and Authorization

Uses middleware to authenticate users (api.auth).
Admin-only routes are protected by AdminMiddleware.
Performance Management

CRUD Operations:
Create, read, update, and soft delete (stsDel set to 1) performance records.
Soft Delete:
Records are not permanently deleted; instead, stsDel is updated to 1.
Approval Status (apv):
Default apv is set to P (Pending) on creation.
Updates are restricted if apv is A (Approved) or R (Rejected).
Filtering

Performances can be filtered by:
month and year (required).
apv status (P, A, R, or all if apv is null).
NIP (optional, for user-specific filtering).
Caching

Monthly performance data is cached for improved performance.
Soft Delete

Instead of deleting records, the stsDel field is updated to 1.
Validation

Ensures data integrity with validation rules for fields like tglKinerja, durasiKinerjaMulai, and durasiKinerjaSelesai.
API Endpoints
Authentication
Login: POST /api/login
Logout: POST /api/logout
Performance
List Performances: GET /api/performances
Returns all performances for the authenticated user (excluding soft-deleted records).
Filter Performances: POST /api/performances/filter
Filters performances by month, year, apv, and optionally NIP.
Create Performance: POST /api/performances
Creates a new performance record (default apv = P).
Show Performance: GET /api/performances/{id}
Retrieves a specific performance record.
Update Performance: PUT /api/performances/{id}
Updates a performance record (restricted if apv = A or R).
Soft Delete Performance: DELETE /api/performances/{id}
Marks a performance record as deleted by setting stsDel = 1.
Middleware
api.auth:

Ensures the user is authenticated.
AdminMiddleware:

Restricts access to admin-only routes.
Returns a 403 Unauthorized response if the user is not an admin.
Database Fields
Performance Table
Field Type Description
id integer Primary key.
nama string Name of the performance task.
penjelasan string Description of the performance task.
tglKinerja date Date of the performance.
durasiKinerjaMulai time Start time of the performance.
durasiKinerjaSelesai time End time of the performance.
durasiKinerja string Duration in HH:MM format.
menitKinerja integer Duration in minutes.
apv string Approval status (P, A, R).
tupoksi string Task responsibility.
periodeKinerja string Performance period (e.g., "March 2025").
target integer Target value for the performance.
satuanTarget string Unit of the target (e.g., "units").
NIP string User identifier (e.g., employee ID).
stsDel boolean Soft delete flag (0 = active, 1 = deleted).
Setup Instructions
Clone the Repository

Install Dependencies

Set Up Environment

Copy .env.example to .env and configure database and other settings.
Run Migrations

Run the Application

Create Storage Link

Notes
Ensure the AdminMiddleware is applied to admin-only routes.
Use the filterByApv method to retrieve performances based on apv, month, and year.
