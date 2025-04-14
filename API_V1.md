# API Response Standards

**Last Updated**: {{DATE}}  
**Version**: v1

## 📌 Response Format

All API responses (success/error) **MUST** follow this structure:

```json
{
    "status": "success | error",
    "data": "payload (object/array) | null",
    "error": {
        "code": "HTTP status code (e.g., 401)",
        "message": "Human-readable error summary",
        "details": "Validation errors or additional context (optional)"
    },
    "last_updated": "ISO 8601 timestamp (e.g., 2023-10-25T14:30:45.000000Z)",
    "message": "Optional success/error description"
}
```
