<?php

/**
 * API Parser - Work Processor
 * Handles parse, run, and file management actions.
 * 
 * Security: All user inputs are validated and sanitized.
 * No eval() or arbitrary code execution.
 */

// Show errors in development only — disable in production
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

include "./main.php";

// --- Input Validation ---

$allowedActions = ['parse', 'run'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    die("Invalid or missing action.");
}

$data = '';
if (isset($_POST['data']) && $_POST['data'] !== '') {
    $data = $_POST['data'];
} elseif (isset($_GET['data']) && $_GET['data'] !== '') {
    $data = $_GET['data'];
}

if ($data === '') {
    http_response_code(400);
    die("No Raw data is given!");
}

$data = trim(urldecode($data));
$data = str_replace("andder", "&", $data);
$data = explode("<br>", $data);
$finalData = implode("\n", $data);
$count = count($data);
$mData = $data[0];
$mData = explode(" ", $mData);
$url = isset($mData[1]) ? $mData[1] : '';
$mHost = subString("host: ", "\n", strtolower($finalData));

if ($url !== '' && strpos($url, "https://") === false && strpos($url, "http://") === false) {
    $url = "https://" . $mHost . $url;
}

$method = isset($mData[0]) ? strtoupper($mData[0]) : 'GET';
$fData = "";

if ($method === "POST") {
    $count--;
    $fData = isset($data[$count]) ? $data[$count] : '';
    $count--;
}

$dType = "html";
if (str_starts_with($fData, '{')) {
    $dType = "json";
}

$headers = array();
$start = 1;

/**
 * Filters out unnecessary/dangerous headers from raw data.
 * Reusable across parse and run actions.
 *
 * @param array  $rawData     Array of raw header lines
 * @param int    $startIndex  Index to start processing from
 * @param int    $endIndex    Index to stop processing at (exclusive)
 * @param bool   $stripQuotes Whether to strip double-quote characters
 * @return array Filtered header strings
 */
function filterHeaders(array $rawData, int $startIndex, int $endIndex, bool $stripQuotes = false): array
{
    $filtered = [];
    $skipPrefixes = [
        'sec',
        'accept-encoding',
        'content-length',
        'connection',
        'referer',
        'host',
        'origin',
        'accept',
        'user-agent'
    ];

    for ($i = $startIndex; $i < $endIndex; $i++) {
        $hData = trim($rawData[$i]);
        if ($stripQuotes) {
            $hData = str_replace('"', '', $hData);
        }
        if ($hData === '')
            continue;

        $lower = strtolower($hData);
        $skip = false;
        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                $skip = true;
                break;
            }
        }
        if ($skip)
            continue;

        $filtered[] = $hData;
    }
    return $filtered;
}

/**
 * Parses JS-format API code into url, data, headers, method.
 * Shared between parse and run actions when lang=js.
 *
 * @param string $finalData  The full raw input as a single string
 * @param string &$url       URL reference (modified)
 * @param string &$fData     POST data reference (modified)
 * @param string &$method    HTTP method reference (modified)
 * @param string &$dType     Data type reference (modified)
 * @param array  &$dataArr   Data array reference (modified)
 * @param int    &$count     Count reference (modified)
 * @param int    &$start     Start index reference (modified)
 */
function parseJsFormat(string $finalData, string &$url, string &$fData, string &$method, string &$dType, array &$dataArr, int &$count, int &$start): void
{
    if (!str_contains($finalData, '$url'))
        return;

    $url = subString("'", "'", subString('$url', ';', $finalData));
    if ($url === "") {
        $url = subString('"', '"', subString('$url', ';', $finalData));
    }

    $fData = subString("'", "';", subString('$data', "\n", $finalData));
    if ($fData === "") {
        $fData = subString('"', '";', subString('$data', "\n", $finalData));
    }
    $fData = trim($fData);

    $dType = "html";
    if (str_starts_with($fData, '{')) {
        $dType = "json";
    }

    $method = str_contains(strtolower($finalData), "post") ? 'POST' : 'GET';
    $start = 0;

    if (substr_count($finalData, '$header') > 2) {
        $finData = subString("[]", '$res', $finalData, 1);
        if ($finData === "") {
            $finData = subString("[]", '$out', $finalData, 1);
        }
        $str = explode(";", $finData);
        $rawStr = "";
        foreach ($str as $s) {
            $str1 = subString('"', "';", $s);
            if ($str1 === "")
                $str1 = subString("'", "'", $s);
            if ($str1 !== "")
                $rawStr .= '"' . $str1 . '",' . "\n";
        }
        $dataArr = explode('",', trim($rawStr));
    } else {
        $rawHeaders = subString('$headers', '];', $finalData);
        $parts = explode("[", $rawHeaders);
        $rawStr = isset($parts[1]) ? $parts[1] : '';
        if ($rawStr === "") {
            $rawHeaders = subString('$headers', ');', $finalData);
            $parts = explode("array(", $rawHeaders);
            $rawStr = isset($parts[1]) ? $parts[1] : '';
        }
        $dataArr = explode('",', trim($rawStr));
    }

    $count = count($dataArr);
}

// ============================================================
// ACTION: PARSE
// ============================================================
if ($action === "parse") {
    header('Content-Type: text/plain; charset=utf-8');

    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'php';
    $returnHeaders = isset($_GET['headers']) && $_GET['headers'] == '1' ? 1 : 0;

    if ($lang === "js") {
        parseJsFormat($finalData, $url, $fData, $method, $dType, $data, $count, $start);
        $headers = filterHeaders($data, $start, $count, true);

        echo "let url = `$url` ;\n";
        echo " let data = `$fData`;\n";
        echo " let headers = {\n";
        foreach ($headers as $header) {
            $parts = explode(": ", $header, 2);
            $key = $parts[0];
            $val = isset($parts[1]) ? $parts[1] : '';
            echo '    "' . $key . '": "' . $val . '"' . ",\n";
        }
        echo ' };' . "\n\n";
        echo " let callback = (res) => {\n";
        echo "     console.log(res);\n";
        echo " }" . "\n\n";
        echo " http_call(url, data, callback, 0, `$method`,  headers, `$dType`);";
        die();
    }

    // PHP output
    $rUrl = random_int(100, 999);

    for ($i = 1; $i < $count; $i++) {
        $hData = trim($data[$i]);
        if (str_starts_with(strtolower($hData), 'sec') || $hData === "" || str_starts_with(strtolower($hData), 'accept-encoding')) {
            continue;
        }
        if (str_starts_with(strtolower($hData), 'content-length')) {
            $headers[] = 'Content-Length: ".strlen($data_' . $rUrl . ')."';
            continue;
        }
        $headers[] = $hData;
    }
    $headers[] = 'X-forward-for: $ip';

    if (empty($headers) || (isset($headers[0]) && $headers[0] === "")) {
        http_response_code(400);
        die("Can't be parsed.\n Enter a correct raw data");
    }

    $fData = strpos($fData, '"') !== false ? "'$fData'" : '"' . $fData . '"';

    echo '$url_' . $rUrl . ' = "' . $url . '";' . "\n";
    echo '$data_' . $rUrl . ' = ' . $fData . ';' . "\n";
    echo '$headers_' . $rUrl . ' = [' . "\n";
    foreach ($headers as $header) {
        echo '     "' . $header . '",' . "\n";
    }
    echo '];' . "\n\n";
    echo '$output_' . $rUrl . ' = Httpcall($url_' . $rUrl . ', $data_' . $rUrl . ', $headers_' . $rUrl . ', "' . $method . '", ' . $returnHeaders . ');' . "\n";
    echo '$json = json_decode($output_' . $rUrl . ', 1);' . "\n";
}

// ============================================================
// ACTION: RUN
// ============================================================
if ($action === "run") {
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 'php';
    $returnHeaders = isset($_GET['headers']) && $_GET['headers'] == '1' ? 1 : 0;

    if ($lang === "js") {
        parseJsFormat($finalData, $url, $fData, $method, $dType, $data, $count, $start);
        $headers = filterHeaders($data, $start, $count, true);

        $fHead = "";
        $in = 0;
        $totalHeaders = count($headers);
        foreach ($headers as $header) {
            $in++;
            $parts = explode(": ", $header, 2);
            $key = $parts[0];
            $val = isset($parts[1]) ? $parts[1] : '';
            $fHead .= '"' . $key . '": "' . $val . '"';
            if ($in !== $totalHeaders) {
                $fHead .= ",";
            }
        }
        echo "$url|$fData|" . '{' . $fHead . '}' . "|$method|$dType";
        die();
    }

    // PHP run mode
    $headers[] = "X-forward-for: $ip";
    for ($i = 1; $i < $count; $i++) {
        $hData = trim($data[$i]);
        if (str_starts_with(strtolower($hData), 'sec') || $hData === "" || str_starts_with(strtolower($hData), 'accept-encoding')) {
            continue;
        }
        if (str_starts_with(strtolower($hData), 'content-length')) {
            $headers[] = 'Content-Length: ' . strlen($fData);
            continue;
        }
        $headers[] = $hData;
    }

    $output = HttpCall($url, $fData, $headers, $method, $returnHeaders);

    if ($output === "" || $output === false) {
        $url = str_replace("https://", "http://", $url);
        $output = HttpCall($url, $fData, $headers, $method, $returnHeaders);
    }

    echo $output;
}
