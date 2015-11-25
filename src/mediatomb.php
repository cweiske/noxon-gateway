<?php
require_once 'Services/MediaTomb.php';

function handleRequestMediatomb($fullPath, $prefix)
{
    global $mediatomb;

    extract($mediatomb);
    $smt = new Services_MediaTomb($user, $pass, $host, $port);

    $path = substr(urldecode($fullPath), strlen($prefix));
    $container = $smt->getContainerByPath($path);
    $listItems = array();
    addPreviousItem($listItems, $fullPath);

    foreach ($container->getContainers() as $subContainer) {
        $listItems[] = getDirItem(
            $subContainer->title,
            $fullPath . urlencode($subContainer->title) . '/'
        );
    }

    foreach ($container->getItemIterator(false) as $item) {
        $listItems[] = getEpisodeItem(
            $item->title,
            $item->url,
            '',
            'MP3'
        );
    }

    sendListItems($listItems);
}
?>
