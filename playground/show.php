<?php
error_reporting(E_ALL | E_STRICT);
define("NODE_TYPE_PROCESSING_INSTRUCTION", 7);

// Load the XML source
$xml = new DOMDocument;
$xml->load('db.xml');

$xsl = new DOMDocument;
$xsl->load($_GET['format']);

$contentType = "text/html";
$noAuthDownload = false;
foreach ($xsl->childNodes as $node) {
    if ($node->nodeType == NODE_TYPE_PROCESSING_INSTRUCTION) {
        $target = $node->target;
        $data = $node->data;
        if ($target == "nackasmu-content-type") {
            $contentType = substr($data, 1, -1);                                
        } elseif ($target == "nackasmu-content-disposition") {
            $contentDisposition = substr($data, 1, -1);                                
        } elseif ($target == "nackasmu-noauth-download") {
            $noAuthDownload = true;                                
        }
    }
}

// Configure the transformer
$proc = new XSLTProcessor;
$proc->importStyleSheet($xsl); // attach the xsl rules

$data = $proc->transformToXML($xml);

if ($noAuthDownload) {
    /*
     * Some browsers, like the one on Android phones, cannot download files which are protected by HTTP Basic Auth. 
     * Hence, it is necessary to copy the requested file to a temporary, non-protected, folder and redirect the 
     * browser to that temporary file instead. The temporary file is given a random name as a security measure.
     */  
    $filename = uniqid();
    file_put_contents('../medlemsregister-temp/' . $filename, $data);
    $query = '?file='.urlencode($filename);
    $query .= '&contentType='.urlencode($contentType);
    $query .= '&contentDisposition='.urlencode($contentDisposition);
    header('Location: http://www.nackasmu.se/medlemsregister-temp/index.php' . $query, true, 307);
    exit;
} else {
    header("Content-Type: $contentType");
    if (isset($contentDisposition)) {
        $contentDisposition = $_GET['contentDisposition'];
        header("Content-disposition: $contentDisposition");
    }
    header("Content-length: " . strlen($data));
    echo $data;
}
?>