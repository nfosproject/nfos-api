# Address Book API Documentation

## Overview
The address book feature allows users to manage multiple shipping addresses with labels and set a default address.

## Database Structure

### Addresses Table
- `id` (UUID) - Primary key
- `user_id` (UUID) - Foreign key to users
- `label` (string) - Address label (Home, Work, Other, etc.)
- `name` (string) - Full name for delivery
- `phone` (string, nullable) - Phone number
- `email` (string, nullable) - Email address
- `address` (text) - Street address
- `city` (string) - City
- `district` (string) - District
- `notes` (text, nullable) - Additional notes
- `is_default` (boolean) - Default shipping address flag
- `created_at`, `updated_at` - Timestamps

## API Endpoints

All endpoints require authentication (`auth:sanctum` middleware).

### 1. Get All Addresses
**GET** `/api/addresses`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "label": "Home",
      "name": "John Doe",
      "phone": "1234567890",
      "email": "john@example.com",
      "address": "123 Main Street",
      "city": "Kathmandu",
      "district": "Kathmandu",
      "notes": null,
      "is_default": true,
      "formatted_address": "123 Main Street, Kathmandu, Kathmandu",
      "created_at": "2025-11-15T10:00:00Z",
      "updated_at": "2025-11-15T10:00:00Z"
    }
  ]
}
```

### 2. Get Single Address
**GET** `/api/addresses/{address_id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "label": "Home",
    "name": "John Doe",
    ...
  }
}
```

### 3. Create Address
**POST** `/api/addresses`

**Request Body:**
```json
{
  "label": "Home",
  "name": "John Doe",
  "phone": "1234567890",
  "email": "john@example.com",
  "address": "123 Main Street",
  "city": "Kathmandu",
  "district": "Kathmandu",
  "notes": "Ring the doorbell",
  "is_default": true
}
```

**Validation Rules:**
- `label`: required, string, max:50
- `name`: required, string, max:255
- `phone`: nullable, string, max:25
- `email`: nullable, email, max:255
- `address`: required, string
- `city`: required, string, max:100
- `district`: required, string, max:100
- `notes`: nullable, string
- `is_default`: sometimes, boolean

**Response:**
```json
{
  "success": true,
  "message": "Address added successfully.",
  "data": { ... }
}
```

**Note:** If `is_default` is set to `true`, all other addresses for the user will be set to `false`.

### 4. Update Address
**PATCH** `/api/addresses/{address_id}`

**Request Body:** (all fields optional)
```json
{
  "label": "Work",
  "name": "John Doe",
  "phone": "0987654321",
  "is_default": false
}
```

**Response:**
```json
{
  "success": true,
  "message": "Address updated successfully.",
  "data": { ... }
}
```

### 5. Delete Address
**DELETE** `/api/addresses/{address_id}`

**Response:**
```json
{
  "success": true,
  "message": "Address deleted successfully."
}
```

### 6. Set Default Address
**POST** `/api/addresses/{address_id}/set-default`

**Response:**
```json
{
  "success": true,
  "message": "Default address updated successfully.",
  "data": { ... }
}
```

**Note:** This automatically unsets other addresses as default.

## Usage Examples

### Create a Home Address
```bash
curl -X POST http://localhost:8000/api/addresses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "label": "Home",
    "name": "John Doe",
    "phone": "1234567890",
    "email": "john@example.com",
    "address": "123 Main Street",
    "city": "Kathmandu",
    "district": "Kathmandu",
    "is_default": true
  }'
```

### Create a Work Address
```bash
curl -X POST http://localhost:8000/api/addresses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "label": "Work",
    "name": "John Doe",
    "phone": "0987654321",
    "address": "456 Business Park",
    "city": "Lalitpur",
    "district": "Lalitpur",
    "is_default": false
  }'
```

### Get All Addresses
```bash
curl -X GET http://localhost:8000/api/addresses \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Set Default Address
```bash
curl -X POST http://localhost:8000/api/addresses/{address_id}/set-default \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Features

✅ Multiple addresses per user
✅ Custom labels (Home, Work, Other, etc.)
✅ Default shipping address
✅ Full CRUD operations
✅ Automatic default management (only one default at a time)
✅ Formatted address string helper
✅ User ownership validation
✅ Proper relationships with User model

## Integration with Orders

When creating orders, you can now reference saved addresses instead of entering them manually each time. The address book provides a better user experience for repeat customers.

