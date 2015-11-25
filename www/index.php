<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
$fullUri = $_SERVER['REQUEST_URI'];
if (isset($_SERVER['REDIRECT_URL'])) {
    $path    = $_SERVER['REDIRECT_URL'];
} else {
    $path = '/';
}
$dataDir = __DIR__ . '/../data/';
$varDir  = realpath(__DIR__ . '/../var') . '/';
$host1 = 'http://radio567.vtuner.com/';
$host2 = 'http://radio5672.vtuner.com/';
if ($_SERVER['HTTP_HOST'] !== '') {
    $host1 = 'http://' . $_SERVER['HTTP_HOST'] . '/';
    $host2 = 'http://' . $_SERVER['HTTP_HOST'] . '/';
}
$cfgFile = $dataDir . 'config.php';
if (file_exists($cfgFile)) {
    include $cfgFile;
}

if (strtolower($fullUri) == '/setupapp/radio567/asp/browsexpa/loginxml.asp?token=0') {
    //initial login for "internet radio" and podcasts
    //lowercase tags
    header('Content-type: text/html');
    readfile($dataDir . 'initial-login.xml');
    exit();
} else if ($fullUri == '/RadioNativeLogin.php') {
    //initial login for "My noxon"
    //this one wants CamelCased tags
    header('Content-type: text/html');
    readfile($dataDir . 'login-mynoxon.xml');
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
    $path = '/internetradio/';
} else if ($path == '/setupapp/radio567/asp/BrowseXML/FavXML.asp') {
    //Internet Radio Station favorites favorited on device
    sendMessage('Unsupported');
} else if ($path == '/RadioNativeFavorites.php') {
    //Favorites, defined via web interface
    sendMessage('Unsupported');
} else if (substr($path, 0, 9) == '/play-url') {
    //play a given URL, but first follow all redirects
    //noxon iRadio Cube does not like too many redirections
    // 3 redirects did not work.
    $url = $_GET['url'];
    header('HTTP/1.0 301 Moved Permanently');
    header('Location: ' . getFinalUrl($url));
    exit();
}

handleRequest(ltrim($path, '/'));

function handleRequest($path)
{
    global $varDir;
    if (strpos($path, '..') !== false) {
        sendMessage('No');
        return;
    }

    if (substr($path, 0, 14) == 'internetradio/') {
        require_once 'mediatomb.php';
        handleRequestMediatomb($path, 'internetradio/');
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
    } else if ($ext == 'sh') {
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
    addPreviousItem($listItems, $path);

    $entries = glob(str_replace('//', '/', $varDir . rtrim($path, '/') . '/*'));
    $count = 0;
    foreach ($entries as $entry) {
        $urlPath = pathEncode(substr($entry, strlen($varDir)));
        $ext = pathinfo($entry, PATHINFO_EXTENSION);
        if (is_dir($entry)) {
            ++$count;
            $listItems[] = getDirItem(basename($entry), $urlPath . '/');
        } else if ($ext == 'url') {
            //podcast
            ++$count;
            $listItems[] = getPodcastItem(basename($entry, '.url'), $urlPath);
        } else if (substr($entry, -8) == '.auto.sh') {
            //automatically execute script while listing this directory
            addScriptOutput($listItems, $entry);
        } else if ($ext == 'txt' || $ext == 'sh') {
            //plain text file
            ++$count;
            $listItems[] = getDirItem(basename($entry, '.' . $ext), $urlPath);
        }
    }
    if (!$count) {
        $listItems[] = getMessageItem('No files or folders');
    }
    sendListItems($listItems);
}

function sendScript($path)
{
    global $varDir;

    $listItems = array();
    addPreviousItem($listItems, $path);

    $fullPath = $varDir . $path;
    addScriptOutput($listItems, $fullPath);
    sendListItems($listItems);
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
    addPreviousItem($listItems, $path);

    $lines = file($varDir . $path);
    addTextLines($listItems, $lines);
    sendListItems($listItems);
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
        . '<Display>' . utf8_decode(htmlspecialchars($line)) . '</Display>'
        . '</Item>';
}

function getDirItem($title, $urlPath)
{
    global $host1, $host2;
    return '<Item>'
        . '<ItemType>Dir</ItemType>'
        . '<Title>' . utf8_decode(htmlspecialchars($title)) . '</Title>'
        . '<UrlDir>' . $host1 . utf8_decode(htmlspecialchars($urlPath)) . '</UrlDir>'
        . '<UrlDirBackUp>' . $host2 . utf8_decode(htmlspecialchars($urlPath)) . '</UrlDirBackUp>'
        . '</Item>';
}

function getEpisodeItem($title, $fullUrl, $desc, $type)
{
    return '<Item>'
        . '<ItemType>ShowEpisode</ItemType>'
        . '<ShowEpisodeName>' . utf8_decode(htmlspecialchars($title)) . '</ShowEpisodeName>'
        . '<ShowEpisodeURL>' . $fullUrl . '</ShowEpisodeURL>'
        . '<ShowDesc>' . utf8_decode(htmlspecialchars($desc)) . '</ShowDesc>'
        . '<ShowMime>' . $type . '</ShowMime>'
        . '</Item>';
}

function getPodcastItem($title, $urlPath)
{
    global $host1;
    return '<Item>'
        . '<ItemType>ShowOnDemand</ItemType>'
        . '<ShowOnDemandName>' . utf8_decode(htmlspecialchars($title)) . '</ShowOnDemandName>'
        . '<ShowOnDemandURL>' . $host1 . utf8_decode(htmlspecialchars($urlPath)) . '</ShowOnDemandURL>'
        . '</Item>';
}

function getMessageItem($msg)
{
    return '<Item>'
        . '<ItemType>Message</ItemType>'
        . '<Message>' . utf8_decode(htmlspecialchars($msg)) . '</Message>'
        . '</Item>';
}

function getPreviousItem($urlPath)
{
    global $host1, $host2;
    return '<Item>'
        . '<ItemType>Previous</ItemType>'
        . '<UrlPrevious>' . $host1 . utf8_decode(htmlspecialchars($urlPath)) . '</UrlPrevious>'
        . '<UrlPreviousBackUp>' . $host1 . utf8_decode(htmlspecialchars($urlPath)) . '</UrlPreviousBackUp>'
        . '</Item>';
}

function addPreviousItem(&$listItems, $urlPath)
{
    $parentDir = dirname($urlPath) . '/';
    if ($parentDir == '/') {
        return;
    }
    $listItems[] = getPreviousItem($parentDir);
}

function getFinalUrl($url)
{
    $ctx = stream_context_set_default(
        array('http' => array('method' => 'HEAD'))
    );
    //get_headers follows redirects automatically
    $headers = get_headers($url, 1);
    if ($headers !== false && isset($headers['Location'])) {
        return end($headers['Location']);
    }
    return $url;
}

function sendMessage($msg)
{
    sendListItems(array(getMessageItem($msg)));
}

function sendListItems($listItems)
{
    $startitems = 1;
    $enditems = 10;
    if (isset($_GET['startitems'])) {
        $startitems = (int) $_GET['startitems'];
    }
    if (isset($_GET['enditems'])) {
        $enditems = (int) $_GET['enditems'];
    }
    //TODO: limit list

    $xml = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";
    $xml .= '<?xml-stylesheet type="text/xsl" href="/html.xsl"?>' . "\n";
    $xml .= '<ListOfItems>' . "\n";
    foreach ($listItems as $item) {
        $xml .= $item . "\n";
    }
    $xml .= "</ListOfItems>\n";

    header('Content-type: text/xml');
    echo $xml;
}
?>
