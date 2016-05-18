<?php
$jsonFile = json_decode(file_get_contents('toc.json'), true);
echo '<ul class="tree">';

createList($jsonFile);
function createList($array) {
    
    foreach($array as $index=>$array) {
        echo '<li id="'.$array['o'].'"><a href="'.$array['url'].'">'.$array['text'].'</a>';
        if(isset($array['children'])) {
            echo '<ul class="children">';
            createList($array['children']);
            echo '</ul></li>';
        }else{
            echo '</li>';
        }
    }
    
}

echo '</ul>';
?>
