<?php
include($_SERVER['DOCUMENT_ROOT'].'\Includes\simple_html_dom.php');
$dir = $_SERVER['DOCUMENT_ROOT'].'\News\\';

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
    $parts = pathinfo($filename);
    if(is_file($filename) && $parts['extension'] == 'php' && $parts['basename'] != 'index.php'){
        $filename = preg_replace("{/}", "\\", $filename);
        $files[] = $filename;
    }
    
}

arsort($files);
$newJson = array();

foreach($files as $file) {
    $html = file_get_html($file);
    //break into parts
    foreach($html->find('.news') as $e) {
        
        // Get time and convert
        $time = str_replace('-', "",$e->find('.time', 0)->innertext);
        $time = date('Y-m-d',strtotime($time));
        
        // Get URL and title
        $urlObj = $e->find('a',0);
        if(isset($urlObj)) {
            $linkText = $urlObj->innertext;
            $url = $urlObj->href;
            $urlObj->outertext = '';
        }else{
            $url = false;
            $linkText = false;
        }
        $title = $e->find('dt',0)->innertext;
        
        // Description
        $desc = $e->find('.description',0)->innertext;
        
        if(array_key_exists($time, $newJson)) {
            $newJson[$time][] = array("url" => $url, "title" => $title, "desc" => $desc, "linkText" => $linkText);
        }else{
            $newJson[$time][] = array("url" => $url, "title" => $title, "desc" => $desc, "linkText" => $linkText);
        }
    }
}

print('<pre>'.json_encode($newJson, JSON_PRETTY_PRINT).'</pre>');

?>
