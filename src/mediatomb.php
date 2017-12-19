<?php
require_once 'Services/MediaTomb.php';

function handleMediatomb($action, $fullPath, $prefix)
{
    global $mediatomb;

    extract($mediatomb);
    try {
        $smt = new Services_MediaTomb($user, $pass, $host, $port);
        if ($action == 'browse') {
            mediatombBrowse($smt, $fullPath, $prefix);
        } else if ($action == 'single') {
            mediatombSingle($smt, $fullPath, $prefix);
        }
    } catch (Exception $e) {
        sendMessage('Mediatomb error: ' . $e->getMessage());
        return;
    }
}

function mediatombBrowse(Services_MediaTomb $smt, $fullPath, $prefix)
{
    global $mediatomb;

    $path = substr($fullPath, strlen($prefix));
    $container = $smt->getContainerByPath($path);
    if ($container === null) {
        sendMessage('Error accessing ' . $fullPath);
        return;
    }

    $listItems = array();

    $it = $container->getItemIterator(false);
    $it->rewind();
    $hasFiles = $it->valid();
    if ($hasFiles && is_array($mediatomb['singleFileDirectories'])) {
        $enableSingle = false;
        foreach ($mediatomb['singleFileDirectories'] as $dir) {
            if (substr($fullPath, 0, strlen($dir)) == $dir) {
                $enableSingle = true;
            }
        }
        if ($enableSingle) {
            $listItems[] = getDirItem(
                'Einzeln',
                pathEncode('.mt-single/' . $path)
            );
        }
    }

    foreach ($container->getContainers() as $subContainer) {
        $listItems[] = getDirItem(
            $subContainer->title,
            pathEncode($fullPath . $subContainer->title) . '/'
        );
    }

    foreach ($container->getItemIterator(false) as $item) {
        mediatombAddFile($listItems, $item);
    }

    sendListItems($listItems, buildPreviousItem($fullPath));
}

function mediatombAddFile(&$listItems, $item)
{
    global $host1;

    $di = $item->getDetailedItem();
    $itemUrl = $item->url;
    if (!clientSupportsType($di->mimetype)) {
        //client wants transcoded file
        //noxon iRadio cube does not want to play .ogg files
        if (isset($GLOBALS['cacheDir']) && $GLOBALS['cacheDir'] != '') {
            $itemUrl = $host1 . 'transcode-cache.php'
                . '?url=' . urlencode($itemUrl);
        } else {
            $itemUrl = $host1 . 'transcode-nocache.php'
                . '?url=' . urlencode($itemUrl);
        }
    }
    $listItems[] = getEpisodeItem(
        $item->title,
        $itemUrl,
        '',
        'MP3'
    );
}

function clientSupportsType($mimetype)
{
    if ($mimetype === 'audio/mpeg') {
        return true;
    }
    $ip = $_SERVER['REMOTE_ADDR'];
    if (isset($GLOBALS['clientSupport'][$ip][$mimetype])
        && $GLOBALS['clientSupport'][$ip][$mimetype] === true
    ) {
        return true;
    }
    return false;
}

/**
 * Single file mode - shows directories that only have a single file in them.
 * Each audio file gets its own virtual directory, containing only the
 * audio file itself.
 *
 * Useful children who want to listen a single story before sleeping,
 * but the noxon's auto switch-off timer is not exactly at the end of
 * the story. So the next story starts already, and the kid complains
 * that it wanted to listen to that as well...
 */
function mediatombSingle(Services_MediaTomb $smt, $fullPath, $prefix)
{
    $path = substr($fullPath, strlen($prefix));

    $parts = explode('/', $path);
    $fileMode = false;
    if (substr(end($parts), 0, 5) == 'file-') {
        $fileMode = true;
        $fileTitle = substr(end($parts), 5);
        $path = substr($path, 0, -strlen($fileTitle) - 5);
    }

    $container = $smt->getContainerByPath($path);
    $listItems = array();

    $previous = null;
    if ($fileMode) {
        //show single file to play
        $previous = buildPreviousItem(pathEncode($fullPath));
        $item = $smt->getSingleItem($container, $fileTitle, false);
        mediatombAddFile($listItems, $item);
    } else {
        $previous = buildPreviousItem(pathEncode('internetradio/' . $path . '/dummy'));

        //browse directory
        foreach ($container->getItemIterator(false) as $item) {
            $listItems[] = getDirItem(
                $item->title,
                pathEncode($fullPath . 'file-' . $item->title)
            );
        }
    }

    sendListItems($listItems, $previous);
}
?>
