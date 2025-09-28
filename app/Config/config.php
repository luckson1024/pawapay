<?php
// --- SIMULATED .env FILE ---
define('PAWAPAY_API_TOKEN', 'eyJraWQiOiIxIiwiYWxnIjoiRVMyNTYifQ.eyJ0dCI6IkFBVCIsInN1YiI6IjEwMDc5IiwibWF2IjoiMSIsImV4cCI6MjA3MzcyMjE5NywiaWF0IjoxNzU4MTg5Mzk3LCJwbSI6IkRBRixQQUYiLCJqdGkiOiIxOWVlMTVjZS0zNDcyLTQ4NDItODZhYi0yMTEzOTY0NzA2MDkifQ.5V41KcLlRau7rox91WulAbPce9cAITjIUTE05oWSxo6SsXIoi3C5EN_4eC8X2KqkrS32-HdmPxxA8luzEY2bgw');
define('PAWAPAY_ENVIRONMENT', 'sandbox'); // 'production' for live

// --- SIMULATED MODESY HELPER FUNCTIONS ---
function getPaymentGateway($name) {
    if ($name == 'pawapay') {
        $gateway = new stdClass();
        $gateway->public_key = PAWAPAY_API_TOKEN;
        $gateway->environment = PAWAPAY_ENVIRONMENT;
        $gateway->status = 1;
        return $gateway;
    }
    return null;
}

function inputPost($key) {
    return $_POST[$key] ?? null;
}

function langBaseUrl() {
    // For our test, we just return a relative path
    return 'https://7d86d274f07e.ngrok-free.app/pawapay-v2-integration';
}

function generate_unique_id() {
    // A simple version of a UUID generator for this test
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function log_system($message, $details = []) {
    // A simple logger for our test
    $log = date('Y-m-d H:i:s') . " - " . $message . " - " . json_encode($details) . "\n";
    file_put_contents('app/logfile.txt', $log, FILE_APPEND);
}

// Dummy DB connection for now, will be replaced by your framework's DB
class DBMock {
    public function table($name) { return $this; }
    public function insert($data) {
        log_system("DB MOCK: Inserting into pending_payments", $data);
        return true;
    }
}
class ConfigDatabase {
    public static function connect() { return new DBMock(); }
}