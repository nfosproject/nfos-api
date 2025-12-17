# Reviews API Documentation

## Overview
The reviews feature allows users to create, read, update, and delete product reviews. Users can rate products (1-5 stars) and optionally provide a title, body, and custom attributes.

## Database Structure

### Product Reviews Table
- `id` (UUID) - Primary key
- `product_id` (UUID) - Foreign key to products
- `user_id` (UUID) - Foreign key to users
- `order_id` (UUID, nullable) - Foreign key to orders (optional, for verified purchase reviews)
- `rating` (tinyint) - Rating from 1 to 5
- `title` (string, nullable) - Review title
- `body` (text, nullable) - Review body/content
- `attributes` (JSON, nullable) - Custom review attributes
- `created_at`, `updated_at` - Timestamps

## API Endpoints

### Public Endpoints

#### 1. Get Product Reviews
**GET** `/api/products/{product_id}/reviews`

**Query Parameters:**
- `rating` (optional) - Filter by rating (1-5)
- `sort_by` (optional) - Sort order: `newest` (default), `oldest`, `highest_rating`, `lowest_rating`
- `per_page` (optional) - Items per page (default: 20, min: 6, max: 60)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "product_id": "uuid",
      "product": {
        "id": "uuid",
        "title": "Product Name",
        "slug": "product-name"
      },
      "user": {
        "id": "uuid",
        "name": "John Doe"
      },
      "order_id": "uuid",
      "rating": 5,
      "title": "Great product!",
      "body": "I really enjoyed using this product.",
      "attributes": null,
      "created_at": "2025-11-15T10:00:00Z",
      "updated_at": "2025-11-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1,
    "average_rating": 4.5,
    "rating_count": 10,
    "rating_distribution": {
      "5": 6,
      "4": 3,
      "3": 1
    }
  }
}
```

### Authenticated Endpoints

All endpoints below require authentication (`auth:sanctum` middleware).

#### 2. Get All Reviews (User's Reviews)
**GET** `/api/reviews`

**Query Parameters:**
- `product_id` (optional) - Filter by product ID
- `my_reviews` (optional, default: true) - Show only authenticated user's reviews
- `user_id` (optional) - Filter by user ID (only if `my_reviews=false`)
- `rating` (optional) - Filter by rating (1-5)
- `per_page` (optional) - Items per page (default: 20, min: 6, max: 60)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "product_id": "uuid",
      "product": {
        "id": "uuid",
        "title": "Product Name",
        "slug": "product-name"
      },
      "user": {
        "id": "uuid",
        "name": "John Doe"
      },
      "order_id": "uuid",
      "rating": 5,
      "title": "Great product!",
      "body": "I really enjoyed using this product.",
      "attributes": null,
      "created_at": "2025-11-15T10:00:00Z",
      "updated_at": "2025-11-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

#### 3. Get Single Review
**GET** `/api/reviews/{review_id}`

**Response:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "product_id": "uuid",
    "product": {
      "id": "uuid",
      "title": "Product Name",
      "slug": "product-name"
    },
    "user": {
      "id": "uuid",
      "name": "John Doe"
    },
    "order_id": "uuid",
    "rating": 5,
    "title": "Great product!",
    "body": "I really enjoyed using this product.",
    "attributes": null,
    "created_at": "2025-11-15T10:00:00Z",
    "updated_at": "2025-11-15T10:00:00Z"
  }
}
```

#### 4. Create Review
**POST** `/api/reviews`

**Request Body:**
```json
{
  "product_id": "uuid",
  "order_id": "uuid",
  "rating": 5,
  "title": "Great product!",
  "body": "I really enjoyed using this product. Highly recommend!",
  "attributes": {
    "pros": ["Fast delivery", "Good quality"],
    "cons": ["Could be cheaper"]
  }
}
```

**Validation Rules:**
- `product_id`: required, uuid, must exist in products table
- `order_id`: optional, uuid, must exist in orders table and belong to the user, must contain the product
- `rating`: required, integer, min:1, max:5
- `title`: optional, string, max:255
- `body`: optional, string
- `attributes`: optional, array

**Response:**
```json
{
  "success": true,
  "message": "Review submitted successfully.",
  "data": {
    "id": "uuid",
    "product_id": "uuid",
    "product": {
      "id": "uuid",
      "title": "Product Name",
      "slug": "product-name"
    },
    "user": {
      "id": "uuid",
      "name": "John Doe"
    },
    "order_id": "uuid",
    "rating": 5,
    "title": "Great product!",
    "body": "I really enjoyed using this product.",
    "attributes": {
      "pros": ["Fast delivery", "Good quality"],
      "cons": ["Could be cheaper"]
    },
    "created_at": "2025-11-15T10:00:00Z",
    "updated_at": "2025-11-15T10:00:00Z"
  }
}
```

**Note:** 
- Users can only create one review per product
- If `order_id` is provided, the system verifies that the order belongs to the user and contains the specified product
- Reviews without `order_id` are allowed (for flexibility)

#### 5. Update Review
**PATCH** `/api/reviews/{review_id}`

**Request Body:** (all fields optional)
```json
{
  "rating": 4,
  "title": "Updated review title",
  "body": "Updated review body",
  "attributes": {
    "pros": ["Fast delivery"]
  }
}
```

**Validation Rules:**
- `rating`: sometimes, required, integer, min:1, max:5
- `title`: nullable, string, max:255
- `body`: nullable, string
- `attributes`: nullable, array

**Response:**
```json
{
  "success": true,
  "message": "Review updated successfully.",
  "data": {
    "id": "uuid",
    "product_id": "uuid",
    "product": {
      "id": "uuid",
      "title": "Product Name",
      "slug": "product-name"
    },
    "user": {
      "id": "uuid",
      "name": "John Doe"
    },
    "order_id": "uuid",
    "rating": 4,
    "title": "Updated review title",
    "body": "Updated review body",
    "attributes": {
      "pros": ["Fast delivery"]
    },
    "created_at": "2025-11-15T10:00:00Z",
    "updated_at": "2025-11-15T11:00:00Z"
  }
}
```

**Note:** Users can only update their own reviews.

#### 6. Delete Review
**DELETE** `/api/reviews/{review_id}`

**Response:**
```json
{
  "success": true,
  "message": "Review deleted successfully."
}
```

**Note:** Users can only delete their own reviews.

## Usage Examples

### Create a Review
```bash
curl -X POST http://localhost:8000/api/reviews \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": "product-uuid",
    "order_id": "order-uuid",
    "rating": 5,
    "title": "Excellent product!",
    "body": "I am very satisfied with this purchase.",
    "attributes": {
      "pros": ["Fast shipping", "Good quality"],
      "cons": []
    }
  }'
```

### Get Product Reviews
```bash
curl -X GET "http://localhost:8000/api/products/product-uuid/reviews?sort_by=highest_rating&per_page=10" \
  -H "Content-Type: application/json"
```

### Get User's Reviews
```bash
curl -X GET "http://localhost:8000/api/reviews?my_reviews=true" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

### Update a Review
```bash
curl -X PATCH http://localhost:8000/api/reviews/review-uuid \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "rating": 4,
    "body": "Updated my review after using it more."
  }'
```

### Delete a Review
```bash
curl -X DELETE http://localhost:8000/api/reviews/review-uuid \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Features

✅ Full CRUD operations for reviews
✅ Rating system (1-5 stars)
✅ Optional title and body text
✅ Custom attributes support (JSON)
✅ Optional order verification (verified purchase reviews)
✅ One review per product per user
✅ User ownership validation
✅ Public product reviews endpoint with statistics
✅ Rating distribution and average calculation
✅ Multiple sort options (newest, oldest, highest/lowest rating)
✅ Pagination support
✅ Proper relationships with Product and User models

## Integration with Points System

Reviews can be linked to the points system. After creating a review, you can call the points endpoint to earn review points:

```bash
curl -X POST http://localhost:8000/api/points/review \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "review_id": "review-uuid",
    "product_id": "product-uuid"
  }'
```

## Integration with Products

Product reviews are automatically included when fetching a product:

```bash
GET /api/products/{product_id}
```

The response includes a `reviews` array with all reviews for that product.


