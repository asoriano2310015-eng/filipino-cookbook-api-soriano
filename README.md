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
- **For XAMPP users:** Open your terminal inside `C:\xampp\htdocs\` and clone the repository there.
- **For Standalone/SQLyog users:** Clone it to your preferred workspace directory.

```bash
git clone [https://github.com/your-username/filipino-cookbook-api-soriano.git]
cd filipino-cookbook-api-soriano
# To see files, access LocalDisk C:, look at users and your OS name, search for the repo folder

# 2. Install dependencies
composer install

# 3. Copy the example environment file and fill in your own values
cp .env.example .env

# 4. Import the SQL database (see Database Setup below)

# 5. Run the API
- **Method A: PHP Built-in Server (Recommended for Standalone users)**
# Keep the terminal open and running
php -S localhost:8000 -t public
# Your API base URL will be: http://localhost:8000

- **Method B: XAMPP / Apache Server**
# If you cloned the project inside C:\xampp\htdocs\, make sure Apache and MySQL are running in the XAMPP Control Panel.
# Your API base URL will be: http://localhost/filipino-cookbook-api-soriano/public/

# 6. Test the endpoints using Thunder Client 
```

## Database Setup
- **Database name:** `filipino_cookbook_api` or `filipino_cookbook_api_your-surname`
- **SQL file:** `database/filipino_cookbook_relational.sql`

**Import instructions:**

Option A: Using phpMyAdmin (XAMPP Users)
1. Open XAMPP Control Panel and start Apache and MySQL.
2. Go to http://localhost/phpmyadmin in your browser.
3. Click New on the left menu, name the database filipino_cookbook_api or filipino_cookbook_api_your-surname, and click Create.
4. Click on your new database, navigate to the Import tab at the top.
5. Choose the filipino_foods_relational.sql file from this project directory and click Import/Go.

Option B: Using SQLyog / Command Line
1. Create a database named filipino_cookbook_api or filipino_cookbook_api_your-surname in your MySQL client.
2. Execute/Import the filipino_foods_relational.sql file script into the database.

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
BASE_PATH=/filipino-cookbook-api-soriano/public
> for XAMPP users
BASE_PATH=
> for php -S users
```
> `.env` is excluded via `.gitignore`. Use `.env.example` as a template with placeholder values only.

## Base URL
The Base URL depends entirely on the server environment you choose to deploy with:
* **Using PHP's Built-in Server (`php -S`):**
```
http://localhost:8000/api
```
* **Using XAMPP (Local Subfolder path):**
```
http://localhost/filipino-cookbook-api-soriano/public/api
```

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
![Public welcome route response](screenshots/welcome.png)
**`GET /api/foods` with a valid token**
![GET /api/foods with a valid token](screenshots/getAllFoods.png)
**`GET /api/foods` with a missing/invalid token**
![GET /api/foods with a missing or invalid token](screenshots/tokenReq.png)
**`GET /api/foods/{id}` with a non-existent ID**
![GET /api/foods/{id} with a non-existent ID](screenshots/nonExID.png)
**`GET /api/foods/search/{food_name}` with no matching name**
![GET /api/foods/search/{food_name} with no matching name](screenshots/noMatch.png)
**`GET /api/categories`**
![GET /api/categories response](screenshots/categories.png)
**`GET /api/ingredients`**
![GET /api/ingredients response](screenshots/ingredients.png)
**`POST /api/foods`**
![POST /api/foods response](screenshots/addFood.png)

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
- `public/.htaccess` (Added to handle Apache URL rewriting for XAMPP subfolder environments)
- `./.env.example` (Added to hide original DB configurations)
- `./.gitignore` (Added to avoid pushing files not meant to be pushed)
- `./README.md` (Added to guide developers)

---

### Instructions for Testing the Enhancements

#### Testing Security & Token Middleware
1. Send a request to `GET /api/foods/categories/1` or `POST /api/ingredients` without providing an `Authorization` header. Confirm that the API returns an HTTP `401 Unauthorized` status with an explicit error message.
![Protected route without Authorization header returning 401](screenshots/catTokenReq.png)

#### Testing GET `/api/foods/categories/{category_id}`
1. Set the request header to include `Authorization: Bearer dmmmsu-cookbook-token-2026`.
2. Issue a `GET` request to `http://localhost:8000/api/foods/categories/1`.
3. Verify that the server returns a status `200 OK` and a JSON array containing only recipes mapped strictly to that target category ID.
4. Repeat the test with a non-existent category (e.g., `/api/foods/categories/999`). Confirm that the API handles it gracefully, returning a `404 Not Found` response.
![GET /api/foods/categories/{category_id} test results](screenshots/catNonExID.png)

#### Testing POST `/api/ingredients`
1. Set the request headers to include `Authorization: Bearer dmmmsu-cookbook-token-2026` and `Content-Type: application/json`.
2. Issue a `POST` request to `http://localhost:8000/api/ingredients` with a brand-new payload:
   ```json
   {
       "ingredient_name": "Soy"
   }
   ```
3. Confirm that the server returns a `201 Created` status with a success message.

![POST /api/ingredients test result](screenshots/addIngr.png)

## Developer Information
- **Name:** SORIANO, Andrea Mae I.
- **Course and Section:** BS Information Technology 4B
- **GitHub Username:** asoriano2310015-eng
- **Repository Link:** https://github.com/asoriano2310015-eng/filipino-cookbook-api-soriano.git
- **Date Completed:** July 2026