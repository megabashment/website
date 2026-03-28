<?php
/**
 * RECIPES API ENDPOINT
 *
 * Handles CRUD operations for recipes (shared family recipe book).
 * All recipes are shared among all authenticated users.
 *
 * Endpoints:
 * - GET /api/recipes.php           — fetch all recipes
 * - POST /api/recipes.php          — create new recipe
 * - PUT /api/recipes.php           — update existing recipe
 * - DELETE /api/recipes.php        — delete recipe
 */

// Load auth check (validates session and CSRF token)
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDB();

    // ─────────────────────────────────────────────────────────────────────
    // GET: Fetch all recipes
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $db->query('SELECT id, client_id, name, ingredients FROM recipes ORDER BY created_at DESC');
        $recipes = $stmt->fetchAll();

        // Transform response: use client_id as id for frontend
        $recipes = array_map(function($r) {
            return [
                'id' => $r['client_id'],
                'name' => $r['name'],
                'ingredients' => $r['ingredients'],
            ];
        }, $recipes);

        http_response_code(200);
        echo json_encode(['ok' => true, 'recipes' => $recipes]);
        exit;
    }

    // Parse JSON body for POST/PUT/DELETE
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // ─────────────────────────────────────────────────────────────────────
    // POST: Create new recipe
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $id = trim($input['id'] ?? '');
        $name = trim($input['name'] ?? '');
        $ingredients = trim($input['ingredients'] ?? '');

        // Validation
        if (empty($id) || strlen($id) > 64) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Ungültige Rezept-ID.']);
            exit;
        }
        if (empty($name) || strlen($name) > 255) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Rezeptname ist erforderlich (max. 255 Zeichen).']);
            exit;
        }
        if (empty($ingredients) || strlen($ingredients) > 10000) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Zutaten erforderlich (max. 10000 Zeichen).']);
            exit;
        }

        // Strip tags for security
        $name = strip_tags($name);
        $ingredients = strip_tags($ingredients);

        // Check if ID already exists
        $checkStmt = $db->prepare('SELECT id FROM recipes WHERE client_id = ?');
        $checkStmt->execute([$id]);
        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'Rezept-ID existiert bereits.']);
            exit;
        }

        // Insert
        $stmt = $db->prepare('INSERT INTO recipes (client_id, name, ingredients, created_by) VALUES (?, ?, ?, ?)');
        $stmt->execute([$id, $name, $ingredients, $_SESSION['user_id']]);

        http_response_code(201);
        echo json_encode([
            'ok' => true,
            'recipe' => [
                'id' => $id,
                'name' => $name,
                'ingredients' => $ingredients,
            ]
        ]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PUT: Update existing recipe
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'PUT') {
        $id = trim($input['id'] ?? '');
        $name = trim($input['name'] ?? '');
        $ingredients = trim($input['ingredients'] ?? '');

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Rezept-ID erforderlich.']);
            exit;
        }

        // Validation
        if (empty($name) || strlen($name) > 255) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Rezeptname ist erforderlich (max. 255 Zeichen).']);
            exit;
        }
        if (empty($ingredients) || strlen($ingredients) > 10000) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Zutaten erforderlich (max. 10000 Zeichen).']);
            exit;
        }

        // Strip tags
        $name = strip_tags($name);
        $ingredients = strip_tags($ingredients);

        // Check if recipe exists
        $checkStmt = $db->prepare('SELECT id FROM recipes WHERE client_id = ?');
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Rezept nicht gefunden.']);
            exit;
        }

        // Update
        $stmt = $db->prepare('UPDATE recipes SET name = ?, ingredients = ? WHERE client_id = ?');
        $stmt->execute([$name, $ingredients, $id]);

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE: Delete recipe
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'DELETE') {
        $id = trim($input['id'] ?? '');

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Rezept-ID erforderlich.']);
            exit;
        }

        // Check if recipe exists
        $checkStmt = $db->prepare('SELECT id FROM recipes WHERE client_id = ?');
        $checkStmt->execute([$id]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Rezept nicht gefunden.']);
            exit;
        }

        // Delete all week_plan_entries referencing this recipe first (cascade delete)
        $delWeekStmt = $db->prepare('DELETE FROM week_plan_entries WHERE recipe_id = ?');
        $delWeekStmt->execute([$id]);

        // Delete recipe
        $delStmt = $db->prepare('DELETE FROM recipes WHERE client_id = ?');
        $delStmt->execute([$id]);

        http_response_code(200);
        echo json_encode(['ok' => true]);
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Invalid method
    // ─────────────────────────────────────────────────────────────────────
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Methode nicht erlaubt.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Fehler beim Verarbeiten der Anfrage.']);
    error_log('Recipe API error: ' . $e->getMessage());
}
