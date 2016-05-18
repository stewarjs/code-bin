<?php
include($_SERVER['DOCUMENT_ROOT'].'\Includes\simple_html_dom.php');
$dir = $_SERVER['DOCUMENT_ROOT'].'\ClientServices\HR_Payroll\Customer_Notifications\\';

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
    foreach($html->find('main dl') as $e) {
        
        // Get time and convert
        $time = str_replace('-', "",$e->find('.time', 0)->innertext);
        $time = date('Y-m-d',strtotime($time));
        
        // Get URL and title
        $title = $e->find('dt a',0);
        $url = $title->href;
        $title = $title->innertext;
        
        // Description
        $desc = $e->find('.description',0)->innertext;
        
        // Get all keywords
        $allKeywords = '';
        $property = 'data-tag';
        $keywords = $e->find('.keyword button');
        $lastKeyword = end($keywords);
        foreach($keywords as $keyword) {
            $allKeywords .= $keyword->$property;
            if($keyword != $lastKeyword) {
                $allKeywords .= ',';
            }
        }
        if(array_key_exists($time, $newJson)) {
            $newJson[$time][] = array("url" => $url, "title" => $title, "desc" => $desc, "keywords" => $allKeywords);
        }else{
            $newJson[$time][] = array("url" => $url, "title" => $title, "desc" => $desc, "keywords" => $allKeywords);
        }
    }
}

print('<pre>'.json_encode($newJson, JSON_PRETTY_PRINT).'</pre>');

?>
