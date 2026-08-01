# Filipino Cookbook API

## API Description
The Filipino Cookbook API is a RESTful web service that provides structured information about Filipino dishes, including their categories, regional origins, and ingredients.

- **Purpose:** To serve as a centralized source of Filipino food data for use in client applications.
- **Type of information provided:** Food names, categories, origins, cooking instructions, and ingredient lists.
- **Intended users:** Students, developers, and client applications (web or mobile) that need Filipino recipe data.
- **Main functions:** Retrieve foods, categories, and ingredients; search foods by name; filter foods by category; add new foods and ingredients — all protected by Bearer token authentication.
- **Technologies used:** PHP, Slim Framework, MySQL, Composer, JSON.

## Features
- Retrieve all Filipino foods with their category, origin, and ingredients
- Retrieve a single food item by ID
- Search for foods by name 
- Filter foods by category ID
- Retrieve all food categories
- Retrieve all ingredients
- Add a new food entry (with linked ingredients)
- Add a new ingredient
- Authenticate all protected requests using a Bearer token
- Return all responses in structured JSON format
- Environment-variable based configuration (`.env`) for credentials and tokens

## Technologies Used
- PHP
- Slim Framework 4
- MySQL (via PDO)
- Composer
- phpdotenv (environment variable management)
- JSON
- APACHE
- Thunder Client
- Git
- GitHub

## Installation Instructions
```bash
# 1. Clone the repository
git clone https://github.com/your-username/filipino-cookbook-api-soriano.git
cd filipino-cookbook-api-soriano
# To see the files, look into young Local Disk (C:), check users and your windows name. Look for the folder named filipino-cookbook-api-soriano.

# 2. Install dependencies
composer install

# 3. Copy the example environment file and fill in your own values
cp .env.example .env

# 4. Import the SQL database (see Database Setup below)

# 5. Start Apache and MySQL (e.g. via XAMPP Control Panel)

# 6. Run the API using PHP's built-in server (or place the project in your htdocs/www folder)
php -S localhost:8000 -t public

# 7. Test the endpoints using Thunder Client 
```

## Database Setup
- **Database name:** `filipino_cookbook_api`
- **SQL file:** `database/filipino_cookbook_relational.sql`

**Import instructions:**
1. Open phpMyAdmin or your MySQLyog.
2. Create a new database named `filipino_cookbook_api`.
3. Import `database/filipino_cookbook_relational.sql` into that database.

**Tables and relationships:**
```
categories -> foods <- origins
foods -> food_ingredients <- ingredients
```

**Environment configuration (`.env`):**
```
DB_HOST=localhost
DB_USER=YOUR_DATABASE_USERNAME
DB_PASS=YOUR_DATABASE_PASSWORD
DB_NAME=filipino_cookbook_api
API_BEARER_TOKEN=dmmmsu-cookbook-token-2026
```
> `.env` is excluded via `.gitignore`. Use `.env.example` as a template with placeholder values only.

## Base URL
```
http://localhost:8000/api
```
*(Adjust the port/path depending on how you run the server — e.g. `http://localhost/filipino-cookbook-api/public/api` if using XAMPP's htdocs.)*

## Authentication Instructions
All `/api/*` endpoints (except the root `/`) require a Bearer token in the request header.

**Required header:**
```
Authorization: Bearer dmmmsu-cookbook-token-2026
```

**If the token is missing or invalid**, the API responds with:
```json
{
    "status": "error",
    "message": "Unauthorized access. Valid API token is required."
}
```
HTTP status: `401 Unauthorized`

## Endpoint Documentation

### GET /
**Description:** Public welcome/status route. No token required.

**Example response:**
```json
{
    "message": "Welcome to the Secured Filipino Cookbook API",
    "note": "Use a valid Bearer token to access /api endpoints."
}
```

---

### GET /api/foods
**Description:** Returns all foods with their category, origin, and ingredients.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`

**Example response:**
```json
[
    {
        "food_id": 1,
        "food_name": "Adobo",
        "category_name": "Main Dish",
        "origin_name": "Philippines",
        "instructions": "...",
        "ingredients": ["Bay leaves", "Chicken or pork", "Cooking oil", "Garlic", "Peppercorn", "Soy sauce", "Vinegar"]
    }
]
```

---

### GET /api/foods/{id}
**Description:** Returns a single food item by its ID.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`

**Example error response (not found):**
```json
{
    "status": "error",
    "message": "Food not found"
}
```

---

### GET /api/foods/search/{name}
**Description:** Searches for foods whose name partially matches the given string.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`

**Example request:**
```
GET /api/foods/search/adobo
```

---

### GET /api/foods/categories/{category_id}
**Description:** Returns all foods belonging to a specific category.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`

**Example error response (category not found):**
```json
{
    "status": "error",
    "message": "Category with ID 99 does not exist."
}
```

---

### GET /api/categories
**Description:** Returns all food categories.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`

---

### GET /api/ingredients
**Description:** Returns all ingredients in the system.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`

---

### POST /api/foods
**Description:** Adds a new food item, optionally linking it to existing ingredients.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`, `Content-Type: application/json`

**Example request body:**
```json
{
    "food_id": 17,
    "food_name": "Lechon Paksiw",
    "category_id": 4,
    "origin_id": 4,
    "instructions": "Combine chopped leftover lechon pork meat with vinegar, lechon liver sauce, brown sugar, garlic, onions, and peppercorns in a deep pot. Simmer on low heat until the meat absorbs the thick sauce.",
    "ingredient_ids": [26, 40, 64, 45]
}
```

**Example success response:**
```json
{
    "status": "success",
    "message": "Food added successfully."
}
```

---

### POST /api/ingredients
**Description:** Adds a new ingredient.
**Headers:** `Authorization: Bearer dmmmsu-cookbook-token-2026`, `Content-Type: application/json`

**Example request body:**
```json
{
    "ingredient_id": 66,
    "ingredient_name": "Magic Sarap"
}
```

**Example success response:**
```json
{
    "status": "success",
    "message": "Ingredient added successfully."
}
```

## HTTP Status Codes
| Status Code | Meaning |
|---|---|
| 200 | Request completed successfully |
| 201 | Resource created successfully |
| 400 | Invalid request or parameter |
| 401 | Missing or invalid authentication |
| 404 | Requested resource was not found |
| 500 | Internal server error |

## Testing Evidence

**Public Welcome Route**
![Public welcome route response](screenshots/image-1.png)
**`GET /api/foods` with a valid token**
![GET /api/foods with a valid token](screenshots/image-2.png)
**`GET /api/foods` with a missing/invalid token**
![GET /api/foods with a missing or invalid token](screenshots/image-3.png)
**`GET /api/foods/{id}` with a non-existent ID**
![GET /api/foods/{id} with a non-existent ID](screenshots/image-4.png)
**`GET /api/foods/search/{food_name}` with no matching name**
![GET /api/foods/search/{food_name} with no matching name](screenshots/image-6.png)
**`GET /api/categories`**
![GET /api/categories response](screenshots/image-7.png)
**`GET /api/ingredients`**
![GET /api/ingredients response](screenshots/image-8.png)
**`POST /api/foods`**
![POST /api/foods response](screenshots/image-9.png)

## Developer Information
- **Name:** SORIANO, Andrea Mae I.
- **Course and Section:** BS Information Technology 4B
- **GitHub Username:** asoriano2310015-eng
- **Repository Link:** https://github.com/asoriano2310015-eng/filipino-cookbook-api-soriano.git
- **Date Completed:** July 2026

---

## Optional API Enhancements

This repository features advanced API features and security measures implemented beyond the basic laboratory requirements to make the application more robust, secure, and extensible.

---

### New API Endpoints

#### Endpoint 1: Add New Ingredient
- **Endpoint:** `POST /api/ingredients`
- **Description:** Registers a new, unique ingredient into the database master registry.
- **Purpose:** Allows client applications to dynamically expand the available ingredient list before mapping them to recipes.

#### Endpoint 2: Get Foods by Category
- **Endpoint:** `GET /api/foods/categories/{category_id}`
- **Description:** Fetches all dishes grouped under a specific category code along with their nested ingredients.
- **Purpose:** Facilitates front-end UI features like category tabs, filtering, or structured navigation menus.

#### Endpoint 3: Search Food by Name
- **Endpoint:** `GET /api/foods/search/{name}`
- **Description:** Searches the database for recipes matching or partially matching a string parameter.
- **Purpose:** Powers text-based search inputs in the application interface.

---

### Implemented Security Features

- **Bearer Token Authentication Middleware:** Intercepts requests destined for protected routes (`/api/*`) by extracting and evaluating the client's header string against the server's environment token. Missing or invalid credentials automatically drop with an HTTP `401 Unauthorized` response.
- **Strict Input Validation:** Enforces data type assertions on incoming JSON payloads. Rejects payloads with missing parameters, empty strings, or invalid numerical fields (e.g., negative or zero value IDs), returning descriptive HTTP `400 Bad Request` messages.
- **Input Sanitization:** Strips potentially dangerous tags and encodes entities on raw string inputs using `strip_tags()` and `htmlspecialchars()` before passing them into databases. This defends the application against Cross-Site Scripting (XSS) and injection attacks.
- **Prepared SQL Statements:** Utilizes PDO parameterized queries on all search, selection, and insertion operations. User input is bound securely, neutralizing potential SQL Injection (SQLi) vectors.
- **Environment-Variable Configuration:** Sensitive operational targets (such as database credentials and the private authorization token string) are kept entirely out of hardcoded logic. Instead, they are parsed securely out of an external `.env` environment file excluded by `.gitignore`.
- **Secure Error Handling:** Suppresses verbose framework tracking details (`addErrorMiddleware(false, true, true)`). Highly detailed PDO execution exceptions are written safely out to internal server system logs (`error_log`) while providing clients with a clean, unrevealing generic error payload.

---

### Files Modified
- `public/index.php` (Core Slim routing framework, data middleware validation layers, and database interactions)

---

### Instructions for Testing the Enhancements

#### Testing Security & Token Middleware
1. Send a request to `GET /api/foods/categories/1` or `POST /api/ingredients` without providing an `Authorization` header. Confirm that the API returns an HTTP `401 Unauthorized` status with an explicit error message.
![Protected route without Authorization header returning 401](screenshots/image-11.png)

#### Testing GET `/api/foods/categories/{category_id}`
1. Set the request header to include `Authorization: Bearer dmmmsu-cookbook-token-2026`.
2. Issue a `GET` request to `http://localhost:8000/api/foods/categories/1`.
3. Verify that the server returns a status `200 OK` and a JSON array containing only recipes mapped strictly to that target category ID.
4. Repeat the test with a non-existent category (e.g., `/api/foods/categories/999`). Confirm that the API handles it gracefully, returning a `404 Not Found` response.
![GET /api/foods/categories/{category_id} test results](screenshots/image-12.png)

#### Testing POST `/api/ingredients`
1. Set the request headers to include `Authorization: Bearer dmmmsu-cookbook-token-2026` and `Content-Type: application/json`.
2. Issue a `POST` request to `http://localhost:8000/api/ingredients` with a brand-new payload:
   ```json
   {
       "ingredient_id": 73,
       "ingredient_name": "Sago"
   }
   ```
3. Confirm that the server returns a `201 Created` status with a success message.

![POST /api/ingredients test result](screenshots/image-13.png)
