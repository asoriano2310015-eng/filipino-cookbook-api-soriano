<?php
/**
 * Filipino Cookbook API
 * Built using the Slim 4 framework.
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// Load Composer's autoloader to fetch framework dependencies
require __DIR__ . '/../vendor/autoload.php';

// Initialize Dotenv to read credentials from the environment variables (.env file)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad won't crash your app if the file is missing

// Create the core Slim Application instance
$app = AppFactory::create();

// Use to automatically detect the base path for XAMPP subfolders, or default to empty for php -S
$basePath = str_replace('/index.php', '',$_SERVER['SCRIPT_NAME']);
$app->setBasePath($basePath);


/**
 * DATABASE CONNECTION (PDO)
 * Establishes a connection to the MySQL database using environment variables.
 * Includes generic fallbacks if environment values are absent.
 */
function getDB() {
    $dbhost = $_ENV['DB_HOST'] ?? 'localhost';
    $dbuser = $_ENV['DB_USER'] ?? 'root';
    $dbpass = $_ENV['DB_PASS'] ?? ''; 
    $dbname = $_ENV['DB_NAME'] ?? '';

    // Set configuration string for MySQL PDO connection using UTF-8 encoding
    $mysql_conn_string = "mysql:host=$dbhost;dbname=$dbname;charset=utf8";
    $dbConnection = new PDO($mysql_conn_string, $dbuser, $dbpass);
    
    // Configure PDO to throw exceptions on errors and fetch records as associative arrays
    $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $dbConnection;
}

/**
 * TOKEN-BASED SECURITY MIDDLEWARE
 * Intercepts requests destined for protected routes to validate the Bearer token.
 */
$validateToken = function (Request $request, $handler) {
    // Fetch the token from the environment variable safely.
    $expectedToken = $_ENV['API_BEARER_TOKEN'] ?? '';
    
    // Read the authorization string sent by the client
    $authHeader = $request->getHeaderLine('Authorization');
    
    // Parse the token header using a Regular Expression matching "Bearer <token>"
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
        // Validate the extracted token against our server's expected environment token
        if ($token === $expectedToken) {
            return $handler->handle($request); // Access granted, proceed to the requested route
        }
    }

    // Access Denied: Create an unauthorized JSON response payload
    $response = new \Slim\Psr7\Response();
    $payload = json_encode([
        "status" => "error",
        "message" => "Unauthorized access. Valid API token is required."
    ], JSON_PRETTY_PRINT);
    
    $response->getBody()->write($payload);
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(401);
};


// REQUIRED API ENDPOINTS


/**
 * 1. Public Welcome Route (No token required)
 * GET /
 * Purpose: Provides a simple status/landing check for the API.
 */
$app->get('/', function (Request $request, Response $response) {
    $payload = json_encode([
        "message" => "Welcome to the Secured Filipino Cookbook API",
        "note" => "Use a valid Bearer token to access /api endpoints."
    ], JSON_PRETTY_PRINT);
    
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

/**
 * 2. Get All Foods (Token required)
 * GET /api/foods
 * Purpose: Returns all food items along with categorized details and their list of ingredients.
 */
$app->get('/api/foods', function (Request $request, Response $response) {
    try {
        $db = getDB();
        // Fetch core information by joining primary food fields with categories and origins tables
        $sql = "SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions 
                FROM foods f
                JOIN categories c ON f.category_id = c.category_id
                JOIN origins o ON f.origin_id = o.origin_id";
        
        $stmt = $db->query($sql);
        $foods = $stmt->fetchAll();

        // Loop through each food to fetch and nest its corresponding relational ingredients
        foreach ($foods as &$food) {
            $stmt_ing = $db->prepare("SELECT i.ingredient_name 
                                      FROM food_ingredients fi
                                      JOIN ingredients i ON fi.ingredient_id = i.ingredient_id
                                      WHERE fi.food_id = ?");
            $stmt_ing->execute([$food['food_id']]);
            // Save query results as a clean list/array of strings assigned under 'ingredients'
            $food['ingredients'] = $stmt_ing->fetchAll(PDO::FETCH_COLUMN);
        }

        $response->getBody()->write(json_encode($foods, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        error_log($e->getMessage()); // Safely log system error information to server logs
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);

/**
 * 3. Get Food by ID (Token required)
 * GET /api/foods/{id}
 * Purpose: Look up a singular recipe using its unique primary identification integer.
 */
$app->get('/api/foods/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    try {
        $db = getDB();
        $sql = "SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions 
                FROM foods f
                JOIN categories c ON f.category_id = c.category_id
                JOIN origins o ON f.origin_id = o.origin_id
                WHERE f.food_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $food = $stmt->fetch();

        // Return a 404 error response payload if no row was matched
        if (!$food) {
            $payload = json_encode(["status" => "error", "message" => "Food not found"], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Fetch ingredients assigned strictly to this single validated food record
        $stmt_ing = $db->prepare("SELECT i.ingredient_name 
                                  FROM food_ingredients fi
                                  JOIN ingredients i ON fi.ingredient_id = i.ingredient_id
                                  WHERE fi.food_id = ?");
        $stmt_ing->execute([$id]);
        $food['ingredients'] = $stmt_ing->fetchAll(PDO::FETCH_COLUMN);

        $response->getBody()->write(json_encode($food, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);

/**
 * 4. Search Food by Name (Token required)
 * GET /api/foods/search/{name}
 * Purpose: Allows simple string matching search patterns against dish names.
 */
$app->get('/api/foods/search/{name}', function (Request $request, Response $response, array $args) {
    $name = trim($args['name']);

    // Input validation: reject empty or whitespace-only search terms
    if ($name === '') {
        $payload = json_encode([
            "status" => "error",
            "message" => "Search term cannot be empty."
        ], JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $db = getDB();
        $sql = "SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions 
                FROM foods f
                JOIN categories c ON f.category_id = c.category_id
                JOIN origins o ON f.origin_id = o.origin_id
                WHERE f.food_name LIKE ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute(["%$name%"]); // Encapsulate string with wildcard SQL matchers safely
        $foods = $stmt->fetchAll();

        // Return a 404 response if no foods matched the search term
        if (empty($foods)) {
            $payload = json_encode([
                "status" => "error",
                "message" => "No foods found matching '$name'."
            ], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Fetch matching sub-ingredients for all records found in search parameters
        foreach ($foods as &$food) {
            $stmt_ing = $db->prepare("SELECT i.ingredient_name 
                                      FROM food_ingredients fi
                                      JOIN ingredients i ON fi.ingredient_id = i.ingredient_id
                                      WHERE fi.food_id = ?");
            $stmt_ing->execute([$food['food_id']]);
            $food['ingredients'] = $stmt_ing->fetchAll(PDO::FETCH_COLUMN);
        }

        $response->getBody()->write(json_encode($foods, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);

/**
 * 5. Get All Categories (Token required)
 * GET /api/categories
 * Purpose: Pulls standard list of organizational categories.
 */
$app->get('/api/categories', function (Request $request, Response $response) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM categories");
        $categories = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode($categories, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);

/**
 * 6. Get All Ingredients (Token required)
 * GET /api/ingredients
 * Purpose: Gathers list of all usable raw ingredients stored inside the master registry.
 */
$app->get('/api/ingredients', function (Request $request, Response $response) {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT * FROM ingredients");
        $ingredients = $stmt->fetchAll();
        
        $response->getBody()->write(json_encode($ingredients, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);


/**
 * 7. Add New Food (Token required)
 * POST /api/foods
 * Purpose: Receives structured payload configurations to append a brand new record to the recipe database.
 */
$app->post('/api/foods', function (Request $request, Response $response) {
    try {
        $db = getDB();
        // Parse incoming raw request body data string straight into an associative PHP Array
        $input = json_decode($request->getBody()->getContents(), true);

        // Input Check: Ensure JSON format wasn't broken or empty
        if (!$input) {
            $payload = json_encode(["status" => "error", "message" => "Invalid or empty JSON payload provided."], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Deep Input Validation: Confirm numerical IDs are present, positive integers, and not zero
        $integerFields = ['food_id', 'category_id', 'origin_id'];
        foreach ($integerFields as $field) {
            if (!isset($input[$field]) || filter_var($input[$field], FILTER_VALIDATE_INT) === false || intval($input[$field]) <= 0) {
                $payload = json_encode(["status" => "error", "message" => "The field '" . $field . "' must be a valid positive integer."], JSON_PRETTY_PRINT);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        // String Content Validation: Check if text fields are missing or strictly blank spacing entries
        if (empty($input['food_name']) || trim($input['food_name']) === '' || empty($input['instructions']) || trim($input['instructions']) === '') {
            $payload = json_encode(["status" => "error", "message" => "Food name and instructions fields are required and cannot be blank."], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Sanitization & Strict Type-Casting (Protects application from malicious injection inputs)
        $food_id = intval($input['food_id']);
        $category_id = intval($input['category_id']);
        $origin_id = intval($input['origin_id']);
        $food_name = htmlspecialchars(strip_tags(trim($input['food_name'])), ENT_QUOTES, 'UTF-8');
        $instructions = htmlspecialchars(strip_tags(trim($input['instructions'])), ENT_QUOTES, 'UTF-8');

        // Loop validation structure sanitizing item values passed inside the linked ingredients array
        $clean_ingredient_ids = [];
        if (!empty($input['ingredient_ids']) && is_array($input['ingredient_ids'])) {
            foreach ($input['ingredient_ids'] as $ing_id) {
                if (filter_var($ing_id, FILTER_VALIDATE_INT) !== false && intval($ing_id) > 0) {
                    $clean_ingredient_ids[] = intval($ing_id);
                }
            }
        }

        // Integrity Check: Check database to prevent entry duplication across unique ID configurations or primary names
        $checkStmt = $db->prepare("SELECT food_id, food_name FROM foods WHERE food_id = ? OR food_name = ?");
        $checkStmt->execute([$food_id, $food_name]);
        $existingFood = $checkStmt->fetch();

        if ($existingFood) {
            // Provide descriptive context describing exact record constraint failure
            if ($existingFood['food_id'] == $food_id) {
                $msg = "Food ID number is already assigned to another dish.";
            } else {
                $msg = "Food item name already exists.";
            }
            
            $payload = json_encode(["status" => "error", "message" => $msg], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Write Phase 1: Insert core entry profile properties into primary recipes table
        $sql = "INSERT INTO foods (food_id, food_name, category_id, origin_id, instructions) VALUES (?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $food_id,
            $food_name,
            $category_id,
            $origin_id,
            $instructions
        ]);

        // Write Phase 2: Insert individual structural pivot mapping relations into bridge table 
        // FIXED BUG: Swapped non-existent `$newFoodId` variable parameter out for operational correct `$food_id`
        if (!empty($clean_ingredient_ids)) {
            $stmt_ing = $db->prepare("INSERT INTO food_ingredients (food_id, ingredient_id) VALUES (?, ?)");
            foreach ($clean_ingredient_ids as $ingredient_id) {
                $stmt_ing->execute([$food_id, $ingredient_id]);
            }
        }

        $payload = json_encode(["status" => "success", "message" => "Food added successfully."], JSON_PRETTY_PRINT);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);

/**
 * 8. Add New Ingredients (Token required)
 * POST /api/ingredients
 * Purpose: Registers alternative new singular dynamic ingredient identifiers into system tables.
 */
$app->post('/api/ingredients', function (Request $request, Response $response) {
    try {
        $db = getDB();
        $input = json_decode($request->getBody()->getContents(), true);
        
        if (!$input) {
            $payload = json_encode(["status" => "error", "message" => "Invalid or empty JSON payload provided."], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verify key structural elements exist and check integer type sizes
        if (!isset($input['ingredient_id']) || filter_var($input['ingredient_id'], FILTER_VALIDATE_INT) === false || intval($input['ingredient_id']) <= 0) {
            $payload = json_encode(["status" => "error", "message" => "Ingredient ID must be a valid positive integer."], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (empty($input['ingredient_name']) || trim($input['ingredient_name']) === '') {
            $payload = json_encode(["status" => "error", "message" => "Ingredient name is required and cannot be empty."], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Run data sanitization patterns cleanly
        $ingredient_id = intval($input['ingredient_id']);
        $ingredient_name = htmlspecialchars(strip_tags(trim($input['ingredient_name'])), ENT_QUOTES, 'UTF-8');

        // Check for conflicts inside records before running insertions
        $checkStmt = $db->prepare("SELECT ingredient_id, ingredient_name FROM ingredients WHERE ingredient_id = ? OR ingredient_name = ?");
        $checkStmt->execute([$ingredient_id, $ingredient_name]);
        $existingIng = $checkStmt->fetch();

        if ($existingIng) {
            if ($existingIng['ingredient_id'] == $ingredient_id) {
                $msg = "Ingredient ID number is already assigned to another ingredient.";
            } else {
                $msg = "Ingredient name already exists.";
            }
            
            $payload = json_encode(["status" => "error", "message" => $msg], JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Commit insertion to components master matrix safely
        $sql = "INSERT INTO ingredients (ingredient_id, ingredient_name) VALUES (?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ingredient_id, $ingredient_name]);

        $payload = json_encode([
            "status" => "success",
            "message" => "Ingredient added successfully."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);

/**
 * 9. Get Foods by Category (Token required)
 * GET /api/foods/categories/{category_id}
 * Purpose: Returns grouping lists sorted via specific category grouping codes.
 */
$app->get('/api/foods/categories/{category_id}', function (Request $request, Response $response, array $args) {
    $category_id = $args['category_id'];
    try {
        $db = getDB();

        // Check to confirm specified category actually exists on records 
        $check_category = $db->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $check_category->execute([$category_id]);
        $category = $check_category->fetch();

        if (!$category) {
            $response->getBody()->write(json_encode([
                "status" => "error", 
                "message" => "Category with ID $category_id does not exist."
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Perform main matching inner tables join queries mapping properties dynamically
        $sql = "SELECT f.food_id, f.food_name, c.category_name, o.origin_name, f.instructions 
                FROM foods f
                JOIN categories c ON f.category_id = c.category_id
                JOIN origins o ON f.origin_id = o.origin_id
                WHERE f.category_id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$category_id]);
        $foods = $stmt->fetchAll();

        // Map relevant ingredients details into the array response layout
        foreach ($foods as &$food) {
            $stmt_ing = $db->prepare("SELECT i.ingredient_name 
                                      FROM food_ingredients fi
                                      JOIN ingredients i ON fi.ingredient_id = i.ingredient_id
                                      WHERE fi.food_id = ?");
            $stmt_ing->execute([$food['food_id']]);
            $food['ingredients'] = $stmt_ing->fetchAll(PDO::FETCH_COLUMN);
        }

        $response->getBody()->write(json_encode($foods, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (PDOException $e) {
        error_log($e->getMessage()); 
        
        $payload = json_encode([
            "status" => "error", 
            "message" => "An internal database error occurred. Please try again later."
        ], JSON_PRETTY_PRINT);
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
})->add($validateToken);


// ERROR HANDLING MIDDLEWARE & APPLICATION RUNNER


// Configures general application tracking middleware handling parameters cleanly.
// Parameter 1: (false) hides full tracking backtraces away from public eyes.
// Parameter 2/3: (true, true) passes inner debug exceptions straight down to server runtime logs.
$errorMiddleware = $app->addErrorMiddleware(false, true, true);

// Execute and listen for incoming HTTP request calls
$app->run();