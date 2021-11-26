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

$document = isset($_GET['subnetId']) ? urldecode($_GET['subnetId']) : '';

$doc_path = realpath(dirname(__FILE__) . "/../../../doc");
$req_uri  = realpath("$doc_path/$document");

if (strpos($req_uri, $doc_path) !== 0) {
    $Result->show("danger", _('Requested resource is not inside doc directory'), true);
}

if (is_file($req_uri) && preg_match('/\.md$/', $document)) {
    # Display file. We know this file exists under doc folder and ends .md

    $md = file_get_contents($req_uri) ? : "";

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
                // Fix up relative URLs
                if ($e->hasAttribute('href')) {
                    $value =  $e->getAttribute('href');
                    if (strpos($value, '#') === 0) {
                        $e->setAttribute('href', create_link("tools/documentation/".urlencode($document)).$value);
                    }
                    elseif (strpos($value, 'http://') !== 0 && strpos($value, 'https://') !== 0) {
                        $e->setAttribute('href', create_link("tools/documentation/$value"));
                    }
                }
            }
        }

        $html = str_replace(['<html>', '</html>'], '', $dom->saveHTML());
    }
}
elseif (is_dir($req_uri)) {
    # Display list of available documents & folders. We know this directory exists under doc folder

    $contents = [];
    if ($dh = opendir($req_uri)) {
        while (($file = readdir($dh)) !== false) {
            if ($file === "." || $file === ".." || $file === "img") { continue; }

            $payload = empty($document) ? urlencode($file) : urlencode("$document/$file");

            if (is_dir("$req_uri/$file")) {
                $contents[$file] = "- ** [$file](".create_link("tools/documentation/$payload").") **";
            } elseif (preg_match('/\.md$/', $file)) {
                $contents[$file] = "- [$file](".create_link("tools/documentation/$payload").")";
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