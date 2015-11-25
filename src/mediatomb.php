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
                $itemUrl = $host1 . 'transcode'
                    . '?mtParentId=' . $container->id
                    . '&mtItemTitle=' . urlencode($item->title);
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

function transcodeMediatombItem($parentId, $title)
{
    global $mediatomb, $host1, $cacheDir, $cacheDirUrl;

    if (!is_writable($cacheDir)) {
        sendMessage('Cache dir not writable');
        return;
    }

    extract($mediatomb);
    try {
        $smt = new Services_MediaTomb($user, $pass, $host, $port);
        $item = $smt->getSingleItem((int) $parentId, $title, false);

        $filename = $item->id . '.mp3';
        $cacheFilePath = $cacheDir . $filename;
        if (!file_exists($cacheFilePath)) {
            transcodeUrlToMp3($item->url, $cacheFilePath);
        }
        if (!file_exists($cacheFilePath)) {
            sendMessage('Error: No mp3 file found');
            return;
        }
        $cacheFileUrl = $cacheDirUrl . $filename;
        header('Location: ' . $cacheFileUrl);
    } catch (Exception $e) {
        sendMessage('Mediatomb error: ' . $e->getMessage());
    }
}

function transcodeUrlToMp3($url, $mp3CacheFilePath)
{
    $tmpfile = tempnam(sys_get_temp_dir(), 'transcode');
    exec(
        'wget --quiet '
        . escapeshellarg($url)
        . ' -O ' . escapeshellarg($tmpfile),
        $output,
        $retval
    );
    if ($retval !== 0) {
        throw new Exception('Error downloading URL');
    }

    exec(
        'ffmpeg'
        . ' -i ' . escapeshellarg($tmpfile)
        . ' ' . escapeshellarg($mp3CacheFilePath)
        . ' 2>&1',
        $output,
        $retval
    );
    unlink($tmpfile);
    if ($retval !== 0) {
        if (file_exists($mp3CacheFilePath)) {
            unlink($mp3CacheFilePath);
        }
        //var_dump($tmpfile, $output);
        throw new Exception('Error transcoding file');
    }
}
?>
