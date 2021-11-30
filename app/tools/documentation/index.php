<?php
# Check we have been included.
if (!isset($User)) { die; }

# verify that user is logged in
$User->check_user_session();

# Check parsedown class is available
$parse_down_class = dirname(__FILE__) . '/../../../functions/parsedown/Parsedown.php';

if (!file_exists($parse_down_class)) {
    $Result->show("danger", _('parsedown library missing, please update submodules'), true);
} else {
    require_once($parse_down_class);
}

$Parsedown = new \Parsedown();
$dom = new \DOMDocument();

if (isset($_GET['sPage'])) {
    $document = rawurldecode($_GET['subnetId']) . "/" . rawurldecode($_GET['sPage']);
}
elseif (isset($_GET['subnetId'])) {
    $document = rawurldecode($_GET['subnetId']);
}
else {
    $document = "";
}

# Strip leading and trailing slashes
$document = preg_replace('/((^\/+)|(\/+$))/', '', $document);

$path_root = realpath(dirname(__FILE__) . "/../../../doc");
$path_doc  = realpath("$path_root/$document");

if (strpos($path_doc, $path_root) !== 0) {
    $Result->show("danger", _('Requested resource is not inside doc directory'), true);
}

if (is_file($path_doc) && preg_match('/\.md$/', $document)) {
    # Display file. We know this file exists under doc folder and ends .md

    $md = file_get_contents($path_doc) ? : "";

    $html = $Parsedown->text(mb_convert_encoding($md, 'HTML-ENTITIES', 'UTF-8')) ? : "";

    # loadHTML requires html root.
    if ($dom->loadHTML("<html>".$html."</html>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING | LIBXML_NOERROR) !== false) {

        $elements = $dom->getElementsByTagName('*');
        if (is_object($elements) && $elements->length>0) {
            foreach($elements as $e) {
                // Add id to headers
                if (in_array($e->tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
                    $id = str_replace(['.', '(', ')'], '', mb_strtolower($e->nodeValue));
                    $id = str_replace(' ', '-', $id);
                    $e->setAttribute('id', $id);
                }
                if ($e->hasAttribute('href')) {
                    $value =  $e->getAttribute('href');
                    if (strpos($value, '#') === 0) {
                        // Fix URI fragment links to header ids within same page
                        $e->setAttribute('href', create_link("tools/documentation/$document").$value);
                    }
                }
                if ($e->hasAttribute('src')) {
                    $value =  $e->getAttribute('src');
                    if (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0) {
                        // Fix relative url img paths
                        $value = preg_replace('/^\/+/', '', $value);
                        $e->setAttribute('src', create_link().$value);
                    }
                }
            }
        }

        $html = str_replace(['<html>', '</html>'], '', $dom->saveHTML());
    }
}
elseif (is_dir($path_doc)) {
    # Display list of available documents & folders. We know this directory exists under doc folder

    $contents = [];
    if ($dh = opendir($path_doc)) {
        while (($file = readdir($dh)) !== false) {
            if (in_array($file, ['.', '..', 'img'])) {
                continue;
            }

            $doc = empty($document) ? rawurlencode($file) : rawurlencode($document)."/".rawurlencode($file);

            if (is_dir("$path_doc/$file")) {
                $contents[$file] = "- ** [$file](".create_link("tools/documentation/$doc").") **";
            }
            elseif (preg_match('/\.md$/', $file)) {
                $contents[$file] = "- [$file](".create_link("tools/documentation/$doc").")";
            }
        }
        closedir($dh);
    }
    ksort($contents);

    $html = $Parsedown->text(implode("\n", $contents)) ? : "";
}
else {
    $Result->show("danger", _('Invalid request'), true);
}

print $Subnets->print_breadcrumbs($Sections, $Subnets, $_GET);
print "<hr><br><br>";
print "<div class='markdown-body'>".$html."</div>";