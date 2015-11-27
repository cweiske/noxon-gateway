<?php
/**
 * Transcode audio file URLs to .mp3 and stream it while
 * transcoding is in progress.
 */
//require_once __DIR__ . '/../src/header.php';

if (!isset($_GET['url'])) {
    errorOut('url parameter missing');
}
$parts = parse_url($_GET['url']);
if ($parts === false || !isset($parts['scheme'])) {
    errorOut('Invalid URL');
}
if ($parts['scheme'] !== 'http' && $parts['scheme'] !== 'https') {
    errorOut('URL is neither http nor https');
}
$url = $_GET['url'];

$cmd = 'ffmpeg'
    . ' -loglevel error'
    . ' -i ' . escapeshellarg($url)
    . ' -f mp3'
    . ' -';

$descriptorspec = array(
    1 => array('pipe', 'w'),// stdout is a pipe that the child will write to
    2 => array('pipe', 'w')//stderr
);

register_shutdown_function('shutdown');

$process = proc_open($cmd, $descriptorspec, $pipes);
if (is_resource($process)) {
    header('Content-type: audio/mpeg');
    while ($data = fread($pipes[1], 10000)) {
        //output to browser
        echo $data;
        //TODO: maybe flush() and ob_flush();
    }

    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $retval = proc_close($process);

    if ($retval !== 0) {
        header('HTTP/1.0 500 Internal Server Error');
        header('Content-type: text/plain');
        echo "Error transcoding\n";
        echo $errors . "\n";
    }
}

function shutdown()
{
    global $process, $pipes;

    if (connection_aborted()) {
        //end ffmpeg and clean temp file
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_terminate($process);
    }
}

function errorOut($msg)
{
    header('HTTP/1.0 400 Bad request');
    header('Content-type: text/plain');
    echo $msg . "\n";
    exit(1);
}
?>
