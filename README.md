# Order Management API

A Laravel-based RESTful API for managing orders and customers. This API provides endpoints for creating, reading, updating, and deleting orders and customers, along with filtering and pagination capabilities.

## Features

- Customer Management
  - Create, read, update, and delete customers
  - Partial updates for customer information
  - Email validation

- Order Management
  - Create, read, update, and delete orders
  - Filter orders by various criteria
  - Pagination support
  - Order statistics

## Requirements

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Laravel 10.x

## Installation

1. Clone the repository:
```bash
git clone https://github.com/Kerolos-George/Order-Management-API.git
cd Order-Management-API
```

2. Install dependencies:
```bash
composer install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env`:
```env
DB_CONNECTION=sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=order_management
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

7. Start the development server:
```bash
php artisan serve
```

## API Endpoints

### Customers

- `GET /api/customers` - List all customers
- `POST /api/customers` - Create a new customer
- `GET /api/customers/{id}` - Get a specific customer
- `PUT /api/customers/{id}` - Update a customer
- `DELETE /api/customers/{id}` - Delete a customer

### Orders

- `GET /api/orders` - List all orders with pagination
- `POST /api/orders` - Create a new order
- `GET /api/orders/{id}` - Get a specific order
- `PUT /api/orders/{id}` - Update an order
- `DELETE /api/orders/{id}` - Delete an order
- `GET /api/orders/stats` - Get order statistics

### Order Filters

The `/api/orders` endpoint supports the following filters:

- `status` - Filter by order status
- `customer_id` - Filter by customer ID
- `product_name` - Filter by product name (case-insensitive)
- `min_price` - Filter by minimum price
- `max_price` - Filter by maximum price
- `min_quantity` - Filter by minimum quantity
- `max_quantity` - Filter by maximum quantity
- `start_date` - Filter by start date
- `end_date` - Filter by end date

### Pagination

The `/api/orders` endpoint supports pagination with the following parameters:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 3)

Example response:
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "current_page": 1,
        "total_pages": 5,
        "per_page": 3,
        "total_items": 15,
        "has_next_page": true,
        "has_previous_page": false,
        "next_page": 2,
        "previous_page": 0
    }
}
```

## Example Usage

### Create a Customer
```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Content-Type: application/json" \
  -d '{"name": "John Doe", "email": "john@example.com"}'
```

### Create an Order
```bash
curl -X POST http://localhost:8000/api/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "product_name": "Laptop",
    "quantity": 2,
    "price": 1500,
    "status": "pending"
  }'
```

### Get Orders with Filters
```bash
curl "http://localhost:8000/api/orders?status=shipped&per_page=5&page=2"
```

## Error Handling

The API returns appropriate HTTP status codes and error messages:

- 200: Success
- 201: Created
- 400: Bad Request
- 404: Not Found
- 422: Validation Error
- 500: Server Error

Example error response:
```json
{
    "success": false,
    "message": "Error message",
    "error": "Detailed error information"
}
```
