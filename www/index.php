<?php
require_once __DIR__ . '/../src/header.php';

$radioPodcastLogin = isset($_GET['token']) && $_GET['token'] == '0'
    && strtolower($path) == '/setupapp/radio567/asp/browsexpa/loginxml.asp';
$myNoxonLogin = $path == '/RadioNativeLogin.php';

if ($radioPodcastLogin || $myNoxonLogin) {
    //initial login for "internet radio", podcasts and "my noxon"
    header('Content-type: text/html');
    readfile($dataDir . 'login-camelcase.xml');
    exit();
} else if ($path == '/setupapp/radio567/asp/BrowseXPA/LoginXML.asp') {
    //"Internet Radio"
    $path = '/internetradio/';
} else if ($path == '/setupapp/radio567/asp/BrowseXPA/navXML.asp') {
    //"Podcasts"
    $path = '/podcasts/';
} else if ($path == '/RadioNative.php') {
    //"My Noxon"
    $path = '/mynoxon/';
} else if ($path == '/setupapp/radio567/asp/BrowseXML/FavXML.asp') {
    //Internet Radio Station favorites favorited on device
    sendMessage('Unsupported');
} else if ($path == '/RadioNativeFavorites.php') {
    //Favorites, defined via web interface
    sendMessage('Unsupported');
}

handleRequest(ltrim($path, '/'));

function handleRequest($path)
{
    global $varDir;
    if (strpos($path, '/../') !== false) {
        sendMessage('No');
        return;
    }

    if (substr($path, 0, 14) == 'internetradio/') {
        require_once 'mediatomb.php';
        handleMediatomb('browse', $path, 'internetradio/');
        return;
    } else if (substr($path, 0, 11) == '.mt-single/') {
        require_once 'mediatomb.php';
        handleMediatomb('single', $path, '.mt-single/');
        return;
    }


    $fullPath = $varDir . $path;
    if (!file_exists($fullPath)) {
        sendMessage('Not found: ' . $path);
        return;
    }

    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (is_dir($fullPath)) {
        sendDir($path);
    } else if ($ext == 'url') {
        require_once 'podcasts.php';
        sendPodcast($path);
    } else if ($ext == 'txt') {
        sendTextFile($path);
    } else if (is_executable($fullPath)) {
        sendScript($path);
    } else {
        sendMessage('Unknown file type');
    }
}

function pathEncode($urlPath)
{
    return str_replace('%2F', '/', rawurlencode($urlPath));
}

function sendDir($path)
{
    global $varDir;

    $listItems = array();
    $enablePaging = true;

    $entries = glob(str_replace('//', '/', $varDir . rtrim($path, '/') . '/*'));
    $count = 0;
    $noCache = false;
    foreach ($entries as $entry) {
        $urlPath = pathEncode(substr($entry, strlen($varDir)));
        $ext = pathinfo($entry, PATHINFO_EXTENSION);

        $titleBase = basename($entry);
        $titleBase = preg_replace('#^[0-9]+_#', '', $titleBase);
        if (is_dir($entry)) {
            ++$count;
            $listItems[] = getDirItem($titleBase, $urlPath . '/');
        } else if ($ext == 'url') {
            //podcast
            ++$count;
            $listItems[] = getPodcastItem(basename($titleBase, '.url'), $urlPath);
        } else if (is_executable($entry)
            && strpos(basename($entry), '.auto') !== false
        ) {
            //automatically execute script while listing this directory
            addScriptOutput($listItems, $entry);
            $enablePaging = false;
        } else if ($ext == 'txt' || is_executable($entry)) {
            //plain text file
            ++$count;
            $listItems[] = getDirItem(basename($titleBase, '.' . $ext), $urlPath);
        } else  if (basename($entry) == 'nocache') {
            $noCache = true;
        }
    }
    if (!$count) {
        $listItems[] = getMessageItem('No files or folders');
    }
    sendListItems(
        $listItems, buildPreviousItem($path),
        $enablePaging, $noCache
    );
}

function sendScript($path)
{
    global $varDir;

    $listItems = array();

    $fullPath = $varDir . $path;
    addScriptOutput($listItems, $fullPath);
    sendListItems($listItems, buildPreviousItem($path), false);
}

function addScriptOutput(&$listItems, $fullPath)
{
    exec($fullPath . ' 2>&1', $output, $retVal);

    if ($retVal == 0) {
        addTextLines($listItems, $output);
    } else {
        $listItems[] = getMessageItem('Error executing script');
        addTextLines($listItems, $output);
    }
}

function sendTextFile($path)
{
    global $varDir;
    $listItems = array();

    $lines = file($varDir . $path);
    addTextLines($listItems, $lines);
    sendListItems($listItems, buildPreviousItem($path));
}

function addTextLines(&$listItems, $lines)
{
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line != '') {
            $listItems[] = getDisplayItem($line);
        }
    }
}

function getDisplayItem($line)
{
    $line = preg_replace('#\s+#', ' ', $line);
    return '<Item>'
        . '<ItemType>Display</ItemType>'
        . '<Display>' . nox_esc($line) . '</Display>'
        . '</Item>';
}

function getDirItem($title, $urlPath)
{
    global $host1, $host2;
    return '<Item>'
        . '<ItemType>Dir</ItemType>'
        . '<Title>' . nox_esc($title) . '</Title>'
        . '<UrlDir>' . $host1 . nox_esc($urlPath) . '</UrlDir>'
        . '<UrlDirBackUp>' . $host2 . nox_esc($urlPath) . '</UrlDirBackUp>'
        . '</Item>';
}

function getEpisodeItem($title, $fullUrl, $desc, $type)
{
    return '<Item>'
        . '<ItemType>ShowEpisode</ItemType>'
        . '<ShowEpisodeName>' . nox_esc($title) . '</ShowEpisodeName>'
        . '<ShowEpisodeURL>' . htmlspecialchars($fullUrl) . '</ShowEpisodeURL>'
        . '<ShowDesc>' . nox_esc($desc) . '</ShowDesc>'
        . '<ShowMime>' . $type . '</ShowMime>'
        . '</Item>';
}

function getPodcastItem($title, $urlPath)
{
    global $host1;
    return '<Item>'
        . '<ItemType>ShowOnDemand</ItemType>'
        . '<ShowOnDemandName>' . nox_esc($title) . '</ShowOnDemandName>'
        . '<ShowOnDemandURL>' . $host1 . nox_esc($urlPath) . '</ShowOnDemandURL>'
        . '</Item>';
}

function getMessageItem($msg)
{
    return '<Item>'
        . '<ItemType>Message</ItemType>'
        . '<Message>' . nox_esc($msg) . '</Message>'
        . '</Item>';
}

function getPreviousItem($urlPath)
{
    global $host1, $host2;
    return '<Item>'
        . '<ItemType>Previous</ItemType>'
        . '<UrlPrevious>' . $host1 . nox_esc($urlPath) . '</UrlPrevious>'
        . '<UrlPreviousBackUp>' . $host1 . nox_esc($urlPath) . '</UrlPreviousBackUp>'
        . '</Item>';
}

function buildPreviousItem($urlPath)
{
    $parentDir = dirname($urlPath) . '/';
    if ($parentDir == '/') {
        return null;
    }
    return getPreviousItem($parentDir);
}

function nox_esc($string)
{
    return utf8_decode(htmlspecialchars($string));
}

function sendMessage($msg)
{
    sendListItems(array(getMessageItem($msg)));
}

function sendListItems(
    $listItems, $previous = null, $enablePaging = true, $noCache = false
) {
    $startitems = 1;
    $enditems   = 100000;
    if (isset($_GET['startitems'])) {
        $startitems = (int) $_GET['startitems'];
    }
    if (isset($_GET['enditems'])) {
        $enditems = (int) $_GET['enditems'];
    }

    if ($enablePaging) {
        $itemCount = count($listItems);
    } else {
        $itemCount = -1;
    }
    if ($previous !== null) {
        $previous .= "\n";
    }

    $xml = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";
    $xml .= '<?xml-stylesheet type="text/xsl" href="/html.xsl"?>' . "\n";
    $xml .= '<ListOfItems>' . "\n";
    if ($noCache) {
        $xml .= "<NoCache>1</NoCache>\n";
    }
    $xml .= '<ItemCount>' . $itemCount . '</ItemCount>' . "\n";
    $xml .= $previous;

    $num = 0;
    foreach ($listItems as $item) {
        ++$num;
        if (!$enablePaging || ($num >= $startitems && $num <= $enditems)) {
            $xml .= $item . "\n";
        }
    }
    $xml .= "</ListOfItems>\n";

    header('Content-type: text/xml; charset=iso-8859-1');
    echo $xml;
}
?>
