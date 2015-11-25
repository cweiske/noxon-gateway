<?php
require_once 'Services/MediaTomb.php';

function handleRequestMediatomb($fullPath, $prefix)
{
    global $mediatomb;

    extract($mediatomb);
    try {
        $smt = new Services_MediaTomb($user, $pass, $host, $port);

        $path = substr(urldecode($fullPath), strlen($prefix));
        $container = $smt->getContainerByPath($path);
        $listItems = array();
        addPreviousItem($listItems, $fullPath);

        foreach ($container->getContainers() as $subContainer) {
            $listItems[] = getDirItem(
                $subContainer->title,
                $fullPath . rawurlencode($subContainer->title) . '/'
            );
        }

        foreach ($container->getItemIterator(false) as $item) {
            $di = $item->getDetailedItem();
            if ($di->mimetype !== 'audio/mpeg') {
                //noxon iRadio cube does not want to play .ogg files
                //FIXME: convert to mp3
                //$di->location (on the server)
            }
            $listItems[] = getEpisodeItem(
                $item->title,
                $item->url,
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
