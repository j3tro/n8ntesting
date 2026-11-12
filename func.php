
<?php

require __DIR__ . "/config.php";

header("Content-Type: application/json; charset=utf-8");

// ------------------------------------------------------------
// JSON RESPONSE HELPER
// ------------------------------------------------------------

function sendJsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

// ------------------------------------------------------------
// VALIDATE REQUEST
// ------------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJsonResponse([
        "success" => false,
        "error" => "Only POST requests are allowed."
    ], 405);
}

// Read URL parameters instead of POST body.
if (!isset($_GET["submit_text"])) {
    sendJsonResponse([
        "success" => false,
        "error" => "Invalid submission. Missing submit_text."
    ], 400);
}

$inputText = trim($_GET["input_text"] ?? "");

if ($inputText === "") {
    sendJsonResponse([
        "success" => false,
        "error" => "Input text cannot be empty."
    ], 400);
}

if (strlen($inputText) > 20000) {
    sendJsonResponse([
        "success" => false,
        "error" => "Input is too large."
    ], 400);
}

// ------------------------------------------------------------
// PREPARE N8N PAYLOAD
// ------------------------------------------------------------

$payload = [
    "message" => $inputText,
    "source" => "noc-http-trigger",
    "timestamp" => date("c")
];

$jsonPayload = json_encode(
    $payload,
    JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($jsonPayload === false) {
    sendJsonResponse([
        "success" => false,
        "error" => "Unable to encode request JSON."
    ], 500);
}

// ------------------------------------------------------------
// SEND REQUEST TO N8N
// ------------------------------------------------------------

$ch = curl_init(N8N_WEBHOOK_URL);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $jsonPayload,

    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Accept: application/json"
    ],

    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => REQUEST_TIMEOUT,
    CURLOPT_FOLLOWLOCATION => false
]);

// Execute exactly once.
$result = curl_exec($ch);

$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);

curl_close($ch);

// ------------------------------------------------------------
// HANDLE CONNECTION ERRORS
// ------------------------------------------------------------

if ($result === false || $curlErrno !== 0) {
    sendJsonResponse([
        "success" => false,
        "input" => $inputText,
        "error" => "Unable to connect to n8n: " . $curlError,
        "curl_error_code" => $curlErrno,
        "get_request" => [
            "status" => "error",
            "response" => null
        ],
        "post_request" => [
            "status" => "error",
            "response" => null
        ],
        "execution_time" => date("c")
    ], 502);
}

// ------------------------------------------------------------
// HANDLE EMPTY RESPONSE
// ------------------------------------------------------------

if (trim($result) === "") {
    sendJsonResponse([
        "success" => false,
        "input" => $inputText,
        "error" => "n8n returned an empty response.",
        "n8n_http_status" => $httpCode,
        "content_type" => $contentType,
        "execution_time" => date("c")
    ], 502);
}

// ------------------------------------------------------------
// DECODE N8N RESPONSE
// ------------------------------------------------------------

$n8nResponse = json_decode($result, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJsonResponse([
        "success" => false,
        "input" => $inputText,
        "error" => "n8n returned invalid JSON: " .
            json_last_error_msg(),
        "n8n_http_status" => $httpCode,
        "n8n_raw_response" => substr($result, 0, 2000),
        "execution_time" => date("c")
    ], 502);
}

if (!is_array($n8nResponse)) {
    sendJsonResponse([
        "success" => false,
        "input" => $inputText,
        "error" => "Unexpected n8n response format.",
        "execution_time" => date("c")
    ], 502);
}

// ------------------------------------------------------------
// HANDLE N8N HTTP ERRORS
// ------------------------------------------------------------

if ($httpCode < 200 || $httpCode >= 300) {
    sendJsonResponse([
        "success" => false,
        "input" => $inputText,
        "error" => "n8n returned HTTP " . $httpCode,
        "n8n_http_status" => $httpCode,
        "n8n_raw_response" => substr($result, 0, 2000),
        "get_request" => [
            "status" => "error",
            "response" => null
        ],
        "post_request" => [
            "status" => "error",
            "response" => null
        ],
        "execution_time" => date("c")
    ], 502);
}

// ------------------------------------------------------------
// EXTRACT GET AND POST RESULTS
// ------------------------------------------------------------

$getRequest = $n8nResponse["get_request"] ?? [
    "status" => "not_returned",
    "response" => null
];

$postRequest = $n8nResponse["post_request"] ?? [
    "status" => "not_returned",
    "response" => null
];

$getOk = ($getRequest["status"] ?? "") === "success";
$postOk = ($postRequest["status"] ?? "") === "success";

$overallSuccess =
    ($n8nResponse["success"] ?? false) === true &&
    $getOk &&
    $postOk;

// ------------------------------------------------------------
// FINAL RESPONSE
// ------------------------------------------------------------

sendJsonResponse([
    "success" => $overallSuccess,
    "input" => $inputText,
    "n8n_http_status" => $httpCode,

    "get_request" => $getRequest,
    "post_request" => $postRequest,

    "error" => $overallSuccess
        ? null
        : ($n8nResponse["error"] ?? "One or more operations failed."),

    "execution_time" =>
        $n8nResponse["execution_time"] ?? date("c")

], 200);
