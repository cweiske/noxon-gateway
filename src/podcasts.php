<?php
function sendPodcastList()
{
    $files = glob(__DIR__ . '/../var/podcasts/*.url');
    if (count($files) == 0) {
        sendMessage('Keine Podcasts');
        return;
    }

    $listItems = array();
    foreach ($files as $file) {
        $title = basename($file, '.url');
        $listItems[] = '<Item>'
            . '<ItemType>ShowOnDemand</ItemType>'
            . '<ShowOnDemandName>' . htmlspecialchars($title) . '</ShowOnDemandName>'
            . '<ShowOnDemandURL>http://radio567.vtuner.com/podcasts/' . urlencode(basename($file)) . '</ShowOnDemandURL>'
            . '</Item>';
    }
    sendListItems($listItems);
}

function sendPodcast($file)
{
    //strip /podcasts/
    $file = substr(urldecode($file), 10);
    if (strpos($file, '..') !== false) {
        sendMessage('No');
        return;
    }

    $path = __DIR__ . '/../var/podcasts/' . $file;
    if (!file_exists($path)) {
        return sendMessage('File does not exist: ' . $file);
    }

    $url = trim(file_get_contents($path));

    $cacheFile = '/tmp/podcast-' . md5($file) . '.xml';
    downloadIfNewer($url, $cacheFile);
    
    $sx = simplexml_load_file($cacheFile);
    $listItems = array();
    foreach ($sx->channel->item as $item) {
        $title = (string) $item->title;
        $desc = (string) $item->description;
        $url = $item->enclosure['url'];

        $listItems[] = '<Item>'
            . '<ItemType>ShowEpisode</ItemType>'
            . '<ShowEpisodeName>' . utf8_decode(htmlspecialchars($title)) . '</ShowEpisodeName>'
            . '<ShowEpisodeURL>http://radio567.vtuner.com/play-url?url=' . urlencode($url) . '</ShowEpisodeURL>'
            //. '<ShowEpisodeURL>' . htmlspecialchars($url) . '</ShowEpisodeURL>'
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