<?php
require_once 'Services/MediaTomb.php';

function handleRequestMediatomb($fullPath, $prefix)
{
    global $mediatomb, $host1;

    extract($mediatomb);
    try {
        $smt = new Services_MediaTomb($user, $pass, $host, $port);

        $path = substr($fullPath, strlen($prefix));
        $container = $smt->getContainerByPath($path);
        $listItems = array();
        addPreviousItem($listItems, $fullPath);

        foreach ($container->getContainers() as $subContainer) {
            $listItems[] = getDirItem(
                $subContainer->title,
                pathEncode($fullPath . $subContainer->title) . '/'
            );
        }

        foreach ($container->getItemIterator(false) as $item) {
            $di = $item->getDetailedItem();
            $itemUrl = $item->url;
            if ($di->mimetype !== 'audio/mpeg') {
                //noxon iRadio cube does not want to play .ogg files
                $itemUrl = $host1 . 'transcode-nocache.php'
                    . '?url=' . urlencode($itemUrl);
            }
            $listItems[] = getEpisodeItem(
                $item->title,
                $itemUrl,
                '',
                'MP3'
            );
        }
    } catch (Exception $e) {
        sendMessage('Mediatomb error: ' . $e->getMessage());
        return;
    }

    sendListItems($listItems);
}
?>
