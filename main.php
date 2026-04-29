<?php

/**
 * API Parser - Core Library
 * 
 * Contains utility functions for HTTP calls, string manipulation,
 * random data generation, encryption, and more.
 * 
 * NOTE: This file is included by multiple scripts. All functions
 * and global variables are initialized here.
 */

date_default_timezone_set('Asia/Kolkata');

// Guard optional dependency — simple_html_dom may not be present
if (file_exists(__DIR__ . '/simple_html_dom.php')) {
    include_once __DIR__ . '/simple_html_dom.php';
}

$starttime = microtime(true);

/**
 * Generate a random first name from the names data file.
 * @return string A trimmed random name
 */
function genName(): string
{
    $filePath = __DIR__ . '/data/names.txt';
    if (!file_exists($filePath))
        return 'User';

    $fetch = file_get_contents($filePath);
    $data = explode(',', $fetch);
    $names = [];
    for ($i = 1; $i < count($data); $i++) {
        if ($i !== 24 && $i !== 59) {
            $names[] = str_replace('"', ' ', $data[$i]);
        }
    }
    if (empty($names))
        return 'User';
    $rand = random_int(0, count($names) - 1);
    return trim($names[$rand]);
}

/**
 * Read a random line from a file, or a specific line by index.
 * 
 * @param string     $fileName Path to the file
 * @param int|string $index    Line index or "none" for random
 * @return string The selected line, trimmed
 */
function randomFileLine(string $fileName, $index = "none"): string
{
    if (!file_exists($fileName))
        return '';
    $lines = explode("\n", trim(file_get_contents($fileName)));
    if ($index !== "none")
        return trim($lines[$index] ?? '');
    return trim($lines[random_int(0, count($lines) - 1)]);
}

/**
 * Calculate elapsed time since script start.
 * @return float Seconds elapsed
 */
function totaltime(): float
{
    global $starttime;
    return microtime(true) - $starttime;
}

/**
 * Query all matching elements from HTML using CSS selector.
 * Requires simple_html_dom library.
 * 
 * @param string $html     HTML content
 * @param string $selector CSS selector
 * @return array Array of innerText values
 */
function querySelectorAll(string $html, string $selector): array
{
    if (!class_exists('simple_html_dom'))
        return [];
    $dom = new simple_html_dom();
    $dom->load($html);
    $elements = $dom->find($selector);
    $result = [];
    foreach ($elements as $element) {
        $result[] = $element->innertext;
    }
    $dom->clear();
    return $result;
}

/**
 * Print multiple arguments separated by pipe characters.
 */
function oprint(): void
{
    $args = func_get_args();
    $count = count($args);
    for ($i = 0; $i < $count - 1; $i++) {
        echo $args[$i] . "|";
    }
    if ($count > 0) {
        echo $args[$count - 1];
    }
}

/**
 * Format a number in Indian Numbering System (lakhs, crores).
 * 
 * @param int|string $amt The amount to format
 * @return string Formatted string with commas
 */
function INRFormat($amt): string
{
    $ret = "";
    $amt = (string) $amt;
    for ($i = 1; $i <= strlen($amt); $i++) {
        if ($i > 4 && $i % 2 === 0 && $amt[strlen($amt) - $i] !== "-") {
            $ret = "," . $ret;
        }
        $ret = $amt[strlen($amt) - $i] . $ret;
        if ($i === 3 && strlen($amt) > 3 && $amt[strlen($amt) - $i - 1] !== "-") {
            $ret = "," . $ret;
        }
    }
    return $ret;
}

/**
 * Perform an HTTP request using cURL.
 * 
 * @param string       $url     Request URL
 * @param string       $data    POST body data
 * @param array        $headers HTTP headers
 * @param string       $method  HTTP method (GET, POST, PUT, etc.)
 * @param int|bool     $return  Whether to include response headers
 * @param string|false $proxy   Proxy address or false
 * @return string|false Response body or false on failure
 */
function HttpCall(string $url, string $data, array $headers, string $method, $return, $proxy = false)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    // SSL verification: enabled by default for security.
    // Set to 0 only for local/dev testing with self-signed certs.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, $return);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    if ($proxy !== false) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }

    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log('cURL error: ' . curl_error($ch) . ' for URL: ' . $url);
    }

    curl_close($ch);
    return $output;
}

/**
 * Print colored terminal output (for CLI scripts).
 * 
 * @param string $str  Text to print
 * @param string $type 'e'=error, 's'=success, 'w'=warning, 'i'=info
 */
function colorLog(string $str, string $type = 'i'): void
{
    $colors = [
        'e' => "\033[31m",  // red
        's' => "\033[32m",  // green
        'w' => "\033[33m",  // yellow
        'i' => "\033[36m",  // cyan
    ];
    $reset = "\033[0m";
    $color = $colors[$type] ?? $colors['i'];
    echo "{$color}{$str} {$reset}\n";
}

/**
 * Add ordinal suffix to a number (1st, 2nd, 3rd, 4th...).
 * 
 * @param int $num The number
 * @return string Number with suffix
 */
function numToSuffix(int $num): string
{
    if (!in_array(($num % 100), [11, 12, 13])) {
        switch ($num % 10) {
            case 1:
                return $num . 'st';
            case 2:
                return $num . 'nd';
            case 3:
                return $num . 'rd';
        }
    }
    return $num . 'th';
}

/**
 * Generate a random alphanumeric string.
 * 
 * @param int $length Desired length
 * @return string Random string
 */
function RandomString(int $length): string
{
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $charLen = strlen($characters);
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[random_int(0, $charLen - 1)];
    }
    return $result;
}

/**
 * Generate a random hex-compatible string (optionally with uppercase).
 * 
 * @param int  $length Desired length
 * @param bool $caps   Include uppercase letters
 * @return string Random string
 */
function RandomHexString(int $length, bool $caps = false): string
{
    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
    if ($caps) {
        $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    $charLen = strlen($characters);
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[random_int(0, $charLen - 1)];
    }
    return $result;
}

/**
 * Generate a random hexadecimal string (0-9, a-f only).
 * 
 * @param int $length Desired length
 * @return string Random hex string
 */
function RandomHex(int $length): string
{
    $characters = '0123456789abcdef';
    $charLen = strlen($characters);
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $characters[random_int(0, $charLen - 1)];
    }
    return $result;
}

/**
 * Generate a random numeric string.
 * 
 * @param int $length Desired length
 * @return string Random digit string
 */
function RandomNumber(int $length): string
{
    $str = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= random_int(0, 9);
    }
    return $str;
}

/**
 * Generate a random IP address in the 210-219.x.x.x range.
 * @return string IP address
 */
function randIp(): string
{
    return random_int(210, 219) . "." .
        random_int(0, 255) . "." .
        random_int(0, 255) . "." .
        random_int(0, 255);
}

/**
 * Save data to a file.
 * 
 * @param string $file_name File path
 * @param string $data      Data to write
 * @return int|false Bytes written or false on failure
 */
function saveData(string $file_name, string $data)
{
    return file_put_contents($file_name, $data);
}

/**
 * Generate a random user agent string.
 * @return string User-Agent header value
 */
function getug(): string
{
    $brands = [
        'Acer',
        'Apple',
        'Asus',
        'BenQ',
        'BlackBerry',
        'Bosch',
        'Celkon',
        'Coolpad',
        'Dell',
        'Gionee',
        'Google',
        'Haier',
        'Honor',
        'HP',
        'HTC',
        'HUAWEI',
        'Infinix',
        'Intex',
        'Karbon',
        'Lenovo',
        'LG',
        'Micromax',
        'Nokia',
        'Oneplus',
        'Oppo',
        'Panasonic',
        'Philips',
        'Realme',
        'Samsung',
        'Sony',
        'vivo',
        'Windows NT 10.0; Win64, x64',
        'Xiaomi',
        'ZTE',
    ];
    $brand = $brands[random_int(0, count($brands) - 1)];
    $ver = random_int(6, 11);
    $sion = random_int(0, 9);
    $version = "$ver.$sion";
    $ll3 = random_int(100, 456);
    return "Mozilla/5.0 ($brand $version) AppleWebKit/$ll3.36 (KHTML, like Gecko) Chrome/$ver.0.4430.212 Safari/$ll3.36 Edg/90.0.818.66";
}

/**
 * Generate a random Indian IP address from known Indian IP ranges.
 * @return string IP address
 */
function indianIp(): string
{
    $ipdata = [
        "223.255." . random_int(244, 247) . "." . random_int(0, 255),
        "223." . random_int(239, 244) . "." . random_int(0, 255) . "." . random_int(0, 255),
        "221.135." . random_int(252, 255) . "." . random_int(0, 255),
        "220.158." . random_int(152, 187) . "." . random_int(0, 255),
        "219.91." . random_int(128, 255) . "." . random_int(0, 255),
        "217.146." . random_int(10, 12) . "." . random_int(0, 255),
    ];
    return $ipdata[random_int(0, count($ipdata) - 1)];
}

/**
 * Generate a random email address.
 * 
 * @param string $domain Custom domain or empty for default
 * @return string Email address
 */
function generateRandMail(string $domain = ""): string
{
    $fname = genName();
    $domains = ["@vintomaper.com", "@labworld.org", "@mentonit.net"];
    if ($domain !== "")
        return $fname . random_int(0, 999) . "@" . $domain;
    return $fname . random_int(0, 999) . $domains[random_int(0, 2)];
}

/**
 * Encrypt plaintext with AES-256-CBC and HMAC verification.
 * 
 * @param string $plaintext Text to encrypt
 * @param string $password  Encryption password
 * @return string IV + HMAC + Ciphertext (raw binary)
 */
function encrypt(string $plaintext, string $password): string
{
    $method = "AES-256-CBC";
    $key = hash('sha256', $password, true);
    $iv = openssl_random_pseudo_bytes(16);

    $ciphertext = openssl_encrypt($plaintext, $method, $key, OPENSSL_RAW_DATA, $iv);
    $hash = hash_hmac('sha256', $ciphertext . $iv, $key, true);

    return $iv . $hash . $ciphertext;
}

/**
 * Decrypt AES-256-CBC ciphertext with HMAC verification.
 * 
 * @param string $ivHashCiphertext IV + HMAC + Ciphertext (raw binary)
 * @param string $password         Decryption password
 * @return string|null Decrypted plaintext or null if verification fails
 */
function decrypt(string $ivHashCiphertext, string $password): ?string
{
    $method = "AES-256-CBC";
    if (strlen($ivHashCiphertext) < 48)
        return null;

    $iv = substr($ivHashCiphertext, 0, 16);
    $hash = substr($ivHashCiphertext, 16, 32);
    $ciphertext = substr($ivHashCiphertext, 48);
    $key = hash('sha256', $password, true);

    if (!hash_equals(hash_hmac('sha256', $ciphertext . $iv, $key, true), $hash)) {
        return null;
    }

    return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Extract a substring between two delimiters.
 * Returns empty string if delimiters are not found.
 * 
 * @param string $start Start delimiter
 * @param string $end   End delimiter
 * @param string $str   Input string
 * @return string Extracted substring or empty string
 */
function subString(string $start, string $end, string $str): string
{
    $parts = explode($start, $str, 2);
    if (!isset($parts[1]))
        return '';
    $afterStart = $parts[1];
    $endParts = explode($end, $afterStart, 2);
    return $endParts[0] ?? '';
}

/**
 * Get the client's IP address.
 * 
 * WARNING: X-Forwarded-For and similar headers are easily spoofable.
 * Only use for informational/logging purposes, never for authentication.
 * 
 * @return string Client IP address
 */
function get_client_ip(): string
{
    // Prefer the direct connection IP for security
    if (getenv('REMOTE_ADDR')) {
        return getenv('REMOTE_ADDR');
    }

    // Fallback to proxy headers (can be spoofed — use with caution)
    $proxyHeaders = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
    ];

    foreach ($proxyHeaders as $header) {
        $value = getenv($header);
        if ($value) {
            // X-Forwarded-For can contain multiple IPs; take the first
            $ips = explode(',', $value);
            return trim($ips[0]);
        }
    }

    return 'UNKNOWN';
}

/**
 * Generate a device ID in hex-dash format.
 * 
 * @param int ...$args Segment lengths
 * @return string e.g. "a1b2c3-d4e5f6-a7b8"
 */
function didMaker(int ...$args): string
{
    $ret = '';
    foreach ($args as $arg) {
        $ret .= RandomHex($arg) . "-";
    }
    return substr($ret, 0, -1);
}

/**
 * Generate a random 10-digit phone number.
 * @return string Phone number
 */
function genNumber(): string
{
    return random_int(6, 9) . RandomNumber(9);
}

/**
 * Initialize all global variables with fresh random data.
 * Call this to regenerate all identity/session data at once.
 */
function generate_all(): void
{
    global $lattitude, $longitude, $fname, $lname, $name, $username,
    $dob, $dob_old, $mob, $gmail, $cEmail, $ip, $indianIp, $randIp,
    $pass, $mPass, $sPass, $nPass, $duid, $did, $imei, $ug, $clientIp,
    $milliseconds, $dateNow, $time, $hours, $minutes, $seconds,
    $PHPSESS, $PHPSESS1, $brands, $rDeviceName;

    $lattitude = random_int(10, 79) . "." . random_int(111111, 999999);
    $longitude = random_int(57, 79) . "." . random_int(111111, 999999);
    $fname = genName();
    $lname = genName();
    $name = $fname . $lname;
    $username = $name . random_int(111, 9999);
    $dob = random_int(1995, 2005) . "-" . random_int(1, 12) . "-" . random_int(1, 28);
    $dob_old = random_int(1950, 2005) . "-" . random_int(1, 12) . "-" . random_int(1, 28);
    $mob = random_int(6, 9) . RandomNumber(9);
    $gmail = $fname . $lname . random_int(1, 999) . "@gmail.com";
    $cEmail = generateRandMail();
    $ip = randIp();
    $indianIp = indianIp();
    $randIp = randIp();
    $pass = $name . RandomString(3);
    $nPass = RandomNumber(8);
    $mPass = strtoupper(RandomString(3)) . $lname . random_int(111, 999);
    $sPass = strtoupper(RandomString(3)) . $lname . "@" . random_int(111, 999);
    $duid = RandomHexString(16);
    $did = RandomHex(16);
    $imei = RandomHex(16);
    $ug = getug();
    $clientIp = get_client_ip();
    $milliseconds = floor(microtime(true) * 1000);
    $dateNow = date('d-m-Y');
    $time = date('H:i:s');
    $hours = date('H');
    $minutes = date('i');
    $seconds = date('s');
    $PHPSESS = RandomHexString(32);
    $PHPSESS1 = RandomHexString(26);

    $brandsFile = __DIR__ . '/data/brands.json';
    if (file_exists($brandsFile)) {
        $brands = json_decode(file_get_contents($brandsFile), true);
        if (is_array($brands) && !empty($brands)) {
            $rDeviceName = $brands[random_int(0, count($brands) - 1)] . "-" . range("A", "Z")[random_int(0, 25)] . random_int(1, 40);
        } else {
            $rDeviceName = 'Unknown-Device';
        }
    } else {
        $brands = [];
        $rDeviceName = 'Unknown-Device';
    }
}

// Initialize all globals once on include
generate_all();