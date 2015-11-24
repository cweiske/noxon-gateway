<?php
function sendPodcast($path)
{
    global $varDir, $host1;

    $file = urldecode($path);
    if (strpos($file, '..') !== false) {
        sendMessage('No');
        return;
    }

    $fullPath = $varDir . $path;
    if (!file_exists($fullPath)) {
        return sendMessage('File does not exist: ' . $path);
    }

    $url = trim(file_get_contents($fullPath));

    $cacheFile = '/tmp/podcast-' . md5($path) . '.xml';
    downloadIfNewer($url, $cacheFile);
    
    $sx = simplexml_load_file($cacheFile);
    $listItems = array();
    addPreviousItem($listItems, $path);

    foreach ($sx->channel->item as $item) {
        $title = (string) $item->title;
        $desc = (string) $item->description;
        $url = $item->enclosure['url'];

        $listItems[] = '<Item>'
            . '<ItemType>ShowEpisode</ItemType>'
            . '<ShowEpisodeName>' . utf8_decode(htmlspecialchars($title)) . '</ShowEpisodeName>'
            . '<ShowEpisodeURL>' . $host1 . 'play-url?url=' . urlencode($url) . '</ShowEpisodeURL>'
            . '<ShowDesc>' . utf8_decode(htmlspecialchars($desc)) . '</ShowDesc>'
            . '<ShowMime>MP3</ShowMime>' 
            . '</Item>';
    }
    sendListItems($listItems);
}


function downloadIfNewer($url, $file)
{
    $lastModified = 0;
    if (file_exists($file)) {
        $lastModified = filemtime($file);
    }

    $ctx = stream_context_create(
        array(
            'http' => array(
                'header' => 'If-Modified-Since: ' . date('r', $lastModified)
            )
        )
    );
    $content = file_get_contents($url, false, $ctx);
    //unfortunately, redirects require manual parsing of this array
    for ($n = count($http_response_header) - 1; $n >= 0; --$n) {
        if (substr($http_response_header[$n], 0, 5) == 'HTTP/') {
            list(, $code) = explode(' ', $http_response_header[$n]);
            break;
        }
    }
    if ($code == 200) {
        file_put_contents($file, $content);
    }
}

?>