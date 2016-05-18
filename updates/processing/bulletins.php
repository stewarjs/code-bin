<?php
include($_SERVER['DOCUMENT_ROOT'].'\Includes\simple_html_dom.php');
$categories = array('Adm_Billings', 'HR_Payroll_Processing', 'Manual_Pay', 'Pub_Notes', 'Reporting', 'Research_Inquiry', 'Retirement_Processing', 'TA_Processing', 'Taxes');
$dir = $_SERVER['DOCUMENT_ROOT'].'\Publications\HR_Payroll\\';

foreach($categories as $catalog) {
    $files = null;
    // Get all files within the publications bucket
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir.$catalog.'\\')) as $filename) {
        $parts = pathinfo($filename);
        if(is_file($filename) && strpos($parts['dirname'],'Bulletins') !== false && $parts['basename'] == 'index.php'){
            $filename = preg_replace("{/}", "\\", $filename);
            $files[] = $filename;
        }

    }
    
    arsort($files);
    $newJson = array();

    foreach($files as $file) {
        $html = file_get_html($file);
        //break into parts
        foreach($html->find('.publication-date') as $e) {
            $supersede = false;
            $bulletin = $e->parent();

            // Get time and convert
            $time = $bulletin->find('.publication-date', 0);
            $time = explode('-',$time->innertext);
            $dateObj   = DateTime::createFromFormat('!y', $time[2]);
            $year = $dateObj->format('Y');
            $time = $year.'-'.$time[0].'-'.$time[1];

            // Get URL, title, and check for supersede
            //$bulletin = $e->find('.publication-title', 0);
            $url = $bulletin->find('a',0)->href;
            if($bulletin->children(0)->first_child()->tag != 'a') {
                $supersede = $url;
                $title = $bulletin->find('strong',0)->innertext;
            }else{
                $title = $bulletin->find('a',0)->innertext;
            }

            //print('<p>'.$title.': '.$url.' || ('.$time.')');
            // Get all keywords
            $allKeywords = '';
            $property = 'data-tag';
            $keywords = $bulletin->find('.tag');
            $lastKeyword = count($keywords);
            $i = 1;
            foreach($keywords as $keyword) {
                $allKeywords .= $keyword->$property;
                if($i != $lastKeyword) {
                    $allKeywords .= ',';
                }
                $i++;
            }
            if(array_key_exists($time, $newJson)) {
                $newJson[$time][] = array("url" => $url, "title" => $title, "keywords" => $allKeywords, "supersede" => $supersede);
            }else{
                $newJson[$time][] = array("url" => $url, "title" => $title, "keywords" => $allKeywords, "supersede" => $supersede);
            }
        }
    }   
    
    print('<p>/*==============================================*/');
    print('<p>/*====');
    print($catalog);
    print('<p>/*====');
    print('<p>/*==============================================*/');
    print('<pre>'.json_encode($newJson, JSON_PRETTY_PRINT).'</pre>');
}
    
?>
