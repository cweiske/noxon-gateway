<?php
/**
 * PHP proxy script that proxies the URL given in the "url" parameter.
 *
 * Sends all incoming headers, and also returns all remote headers.
 * Streams the response, so that large responses should work fine.
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
require_once __DIR__ . '/../data/config.php';
if (!isset($enablePodcastProxy) || $enablePodcastProxy == false) {
    header('HTTP/1.0 403 Forbidden');
    echo "Proxying is not enabled in config.php\n";
    exit(1);
}

if (!isset($_GET['url']) || $_GET['url'] == '') {
    header('HTTP/1.0 400 Bad Request');
    echo "url parameter missing\n";
    exit(1);
}
if (substr($_GET['url'], 0, 7) != 'http://'
    && substr($_GET['url'], 0, 8) != 'https://'
) {
    header('HTTP/1.0 400 Bad Request');
    echo "Only http and https URLs supported\n";
    exit(1);
}

$url = $_GET['url'];

//send original http headers
$headers = [];
foreach (apache_request_headers() as $name => $value) {
    if (strtolower($name) == 'host') {
        continue;
    }
    $headers[] = $name . ': ' . $value;
}
$context = stream_context_create(
    ['http' => ['header' => $headers, 'ignore_errors' => true]]
);

$fp = fopen($url, 'r', false, $context);
if (!$fp) {
    header('HTTP/1.0 400 Bad Request');
    echo "Error fetching URL\n";
    exit(1);
}

//send original headers
if (is_array($http_response_header)) {
    foreach ($http_response_header as $header) {
        header($header);
    }
}

//stream the data in 1kiB blocks
while(!feof($fp)) {
    echo fread($fp, 1024);
    flush();
}
fclose($fp);
?>
