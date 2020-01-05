<?php
/**
 * Follow all location redirects of a given URL and redirect to the final URL.
 *
 * Noxon iRadio Cube does not like too many redirections
 * 3 redirects did not work.
 */
$url = $_GET['url'];
header('HTTP/1.0 301 Moved Permanently');
header('Location: ' . getFinalUrl($url));

function getFinalUrl($url)
{
    $ctx = stream_context_set_default(
        array('http' => array('method' => 'HEAD'))
    );
    //get_headers follows redirects automatically
    $headers = get_headers($url, 1);
    if ($headers !== false && isset($headers['Location'])) {
        if (is_array($headers['Location'])) {
            return end($headers['Location']);
        }
        return $headers['Location'];
    }
    return $url;
}
?>