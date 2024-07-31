<?php
session_start();

// Rate limiting setup
$rate_limit = 5;
$time_frame = 60; // in seconds

if (!isset($_SESSION['request_count'])) {
    $_SESSION['request_count'] = 0;
    $_SESSION['first_request_time'] = time();
}

$_SESSION['request_count']++;
$time_elapsed = time() - $_SESSION['first_request_time'];

if ($time_elapsed > $time_frame) {
    $_SESSION['request_count'] = 1;
    $_SESSION['first_request_time'] = time();
}

if ($_SESSION['request_count'] > $rate_limit) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded. Please try again later.']);
    exit();
}

// Enable caching for 1 minute
header('Cache-Control: max-age=60');

// Set rate limit headers
header('X-RateLimit-Limit: 5');
header('X-RateLimit-Remaining: ' . (5 - $_SESSION['request_count']));
header('X-RateLimit-Reset: ' . ($_SESSION['first_request_time'] + $time_frame));

// Allow cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// List of CSV files
$csv_files = [
    "p1_a.csv", "p1_b.csv"
  ];

// Initialize an empty array to store all quotes
$all_quotes = [];

// Read each CSV file and merge the quotes
foreach ($csv_files as $file) {
    $quotes = array_map('str_getcsv', file($file));
    // Remove header if present
    if (isset($quotes[0]) && is_array($quotes[0]) && in_array('quote', $quotes[0])) {
        array_shift($quotes);
    }
    $all_quotes = array_merge($all_quotes, $quotes);
}

// Select a random quote
$random_quote = $all_quotes[array_rand($all_quotes)];

// Assuming the CSV has columns: quote, author
$response = [
    'quote' => $random_quote[0],
    'author' => $random_quote[1] ?? 'Unknown'
];

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode($response);
?>
