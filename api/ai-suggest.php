<?php
/**
 * AI RECIPE SUGGESTION ENDPOINT
 *
 * POST /api/ai-suggest.php
 * Body: { query: string, existingIngredients: string[] }
 * Response: { ok: true, suggestions: [{ name: string, ingredients: string }] }
 *
 * Requires GEMINI_API_KEY in includes/config.php
 */

require_once '../includes/auth_check.php';
require_once '../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$query = trim($input['query'] ?? '');
$existingIngredients = array_slice($input['existingIngredients'] ?? [], 0, 50);

if (empty($query)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Suchanfrage fehlt.']);
    exit;
}

$query = mb_substr($query, 0, 200);

if (!defined('GEMINI_API_KEY') || empty(GEMINI_API_KEY)) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'KI nicht konfiguriert. Bitte GEMINI_API_KEY in config.php setzen.']);
    exit;
}

// Build context from existing ingredient names for consistency
$existingContext = '';
$filtered = array_filter(array_map('trim', $existingIngredients));
if (!empty($filtered)) {
    $existingContext = "\nBereits verwendete Zutatennamen (für einheitliche Schreibweise nutzen, wo passend):\n"
        . implode(', ', array_slice($filtered, 0, 40)) . "\n";
}

$prompt = <<<PROMPT
Du bist ein Kochassistent für einen deutschen Rezept-Wochenplaner.

Aufgabe: Erstelle 3–5 passende Rezeptvorschläge basierend auf dieser Eingabe: "{$query}"
{$existingContext}
Regeln:
- Rezeptnamen auf Deutsch, prägnant
- Zutatenliste: eine Zutat pro Zeile, mit realistischen Mengenangaben in Klammern, z.B. "Hackfleisch (500g)"
- Verwende gängige deutsche Bezeichnungen
- Keine Zubereitungsschritte, nur Zutaten

Antworte NUR mit einem validen JSON-Array, ohne weiteren Text oder Markdown:
[{"name": "Rezeptname", "ingredients": "Zutat1 (Menge)\nZutat2 (Menge)\n..."}, ...]
PROMPT;

$apiKey = GEMINI_API_KEY;
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . urlencode($apiKey);

$payload = json_encode([
    'contents' => [['parts' => [['text' => $prompt]]]],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'maxOutputTokens' => 2048,
        'temperature' => 0.8,
    ]
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$result   = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || !$result) {
    error_log('Gemini API curl error: ' . $curlErr);
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'KI nicht erreichbar. Bitte später erneut versuchen.']);
    exit;
}

if ($httpCode !== 200) {
    error_log('Gemini API HTTP ' . $httpCode . ': ' . $result);
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'KI-Fehler (HTTP ' . $httpCode . '). Bitte später erneut versuchen.']);
    exit;
}

$geminiResponse = json_decode($result, true);
$text = $geminiResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$text) {
    error_log('Gemini unexpected response: ' . $result);
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'KI hat keine Vorschläge zurückgegeben.']);
    exit;
}

$suggestions = json_decode($text, true);

if (!is_array($suggestions)) {
    error_log('Gemini response not valid JSON array: ' . $text);
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'KI-Antwort konnte nicht verarbeitet werden.']);
    exit;
}

// Sanitize
$cleaned = [];
foreach ($suggestions as $s) {
    $name        = mb_substr(strip_tags(trim($s['name'] ?? '')), 0, 255);
    $ingredients = mb_substr(strip_tags(trim($s['ingredients'] ?? '')), 0, 10000);
    if ($name && $ingredients) {
        $cleaned[] = ['name' => $name, 'ingredients' => $ingredients];
    }
}

if (empty($cleaned)) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'Keine gültigen Vorschläge erhalten.']);
    exit;
}

echo json_encode(['ok' => true, 'suggestions' => $cleaned]);
