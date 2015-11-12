<?php
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/../src/');
$fullUri = $_SERVER['REQUEST_URI'];
$path    = $_SERVER['REDIRECT_URL'];
$dataDir = __DIR__ . '/../data/';

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
    header('Content-type: text/xml');
    sendList('internetradio');
    exit();
} else if ($path == '/setupapp/radio567/asp/BrowseXPA/navXML.asp') {
    //"Podcasts"
    require_once 'podcasts.php';
    sendPodcastList();
    exit();
} else if (substr($path, 0, 9) == '/podcasts') {
    require_once 'podcasts.php';
    sendPodcast($path);
    exit();    
} else if ($path == '/RadioNative.php') {
    //"My Noxon"
    header('Content-type: text/xml');
    sendList('mynoxon');
    exit();
} else if ($path == '/setupapp/radio567/asp/BrowseXML/FavXML.asp') {
    //Internet Radio Station favorites favorited on device
} else if ($path == '/RadioNativeFavorites.php') {
    //Favorites, defined via web interface
} else if (substr($path, 0, 9) == '/play-url') {
    //play a given URL, but first follow all redirects
    //noxon iRadio Cube does not like too many redirections
    // 3 redirects did not work.
    $url = $_GET['url'];
    header('HTTP/1.0 301 Moved Permanently');
    header('Location: ' . getFinalUrl($url));
    exit();
} else {
    sendList(ltrim($path, '/'));
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


function sendList($path)
{
    $startitems = 1;
    $enditems = 10;
    if (isset($_GET['startitems'])) {
        $startitems = (int) $_GET['startitems'];
    }
    if (isset($_GET['enditems'])) {
        $enditems = (int) $_GET['enditems'];
    }

    header('Content-type: text/xml');
    echo <<<XML
<?xml version="1.0" encoding="iso-8859-1" standalone="yes" ?>
<ListOfItems>
  <ItemCount>-1</ItemCount>
  <Item>
    <ItemType>Message</ItemType>
    <Message>$path</Message>
  </Item>
  <Item>
    <ItemType>Dir</ItemType>
    <Title>$path</Title>
    <UrlDir>http://radio567.vtuner.com/$path</UrlDir>
    <UrlDirBackUp>http://radio5672.vtuner.com/$path</UrlDirBackUp>
  </Item>
</ListOfItems>

XML;
}

function sendMessage($msg)
{
    header('Content-type: text/xml');
    $xMsg = htmlspecialchars($msg);
    echo <<<XML
<?xml version="1.0" encoding="iso-8859-1" standalone="yes" ?>
<ListOfItems>
  <Item>
    <ItemType>Message</ItemType>
    <Message>$xMsg</Message>
  </Item>
</ListOfItems>

XML;
}

function sendListItems($listItems)
{
    $xml = '<?xml version="1.0" encoding="iso-8859-1"?>' . "\n";
    $xml .= '<ListOfItems>' . "\n";
    foreach ($listItems as $item) {
        $xml .= $item . "\n";
    }
    $xml .= "</ListOfItems>\n";
    
    header('Content-type: text/xml');
    echo $xml;
}

?>
