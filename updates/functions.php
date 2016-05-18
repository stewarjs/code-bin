<?php
include('simple_html_dom.php');
ini_set('display_errors', 'On');
ini_set('log_errors',0);
error_reporting(E_ALL | E_STRICT);

//START SESSION
/*session_set_cookie_params(0);
session_name('NationalFinanceCenter');
session_start();*/
$GLOBALS['doc_root'] = $_SERVER['DOCUMENT_ROOT'];
$GLOBALS['base_url'] = 'http://'.$_SERVER['SERVER_NAME'];
if(!empty($_GET['org'])) {
	if($_GET['org'] == 'eauth'){
			//SETUP EAUTH SWITCH
			$_SESSION['eAuth'] = true;
	}
}

if(!empty($_GET) && !empty($_GET['cmd'])){
	$cmd = $_GET['cmd'];
	switch($cmd) {
        case 'jobs':
            	displayJobs((!empty($_GET['series']) ? $_GET['series']: 'all'), (!empty($_GET['display']) ? $_GET['display']: 'all'));
        break;
		case 'archiveHook':
			$archive = new archive();
			$archive->setlineOfBusiness($_GET['lob']);
			($_GET['bucket'] != 'all' ? $archive->setBucket($_GET['bucket']) : false);
			$archive->setType(strtolower($_GET['archiveType']));
			$archive->get($_GET['startDate'],$_GET['endDate']);
			
		break;
		case 'previousMonth':
			$current = explode('-', $_GET['current']);
			$month = $current[0];
			$year = $current[1];
			$lob = $_GET['lobSwitch'];
			$month = ($month != 1 ? $month - 1 : 12);
			$year = ($month != 12 ? $year : $year - 1);
            if($_GET['filter'] != 'none'){
                echo draw_calendar($month,$year, $lob, $_GET['filter']);
            }else{
                echo draw_calendar($month,$year, $lob);
            }
		break;
		
		case 'nextMonth':
			$current = explode('-', $_GET['current']);
			$month = $current[0];
			$year = $current[1];
			$month = ($month != 13 ? $month + 1 : 1);
			$year = ($month != 13 ? $year : $year + 1);
			$lob = $_GET['lobSwitch'];
			if($_GET['filter'] != 'none'){
                echo draw_calendar($month,$year, $lob, $_GET['filter']);
            }else{
                echo draw_calendar($month,$year, $lob);
            }
		break;
        
        case 'filterCalendar':
            $current = explode('-', $_GET['current']);
			$month = $current[0];
			$year = $current[1];
			$lob = $_GET['lobSwitch'];
			echo draw_calendar($month,$year, $lob, $_GET['filter']);
        break;
	}
}

function isCurrent($dir, $dirLevel) {
	// $dirLevel = 1: Top level folders: Client Services, Careers, Publications
	// $dirLevel = 2: Mid level folders: HR_Payroll, FMS, Insurance
	// $dirLevel = 3: Lower level folders: Customer Notifications, Communities, Initiatives
	
	$path = strtolower($_SERVER['PHP_SELF']);
	$path = explode('/', $path);
	$URLpart =  null;
	
	if(!is_array($dirLevel)) {
		if($path[$dirLevel] == strtolower($dir)) {
			//return 'current';
			return 'data-place="current"';
		}
	}else{
		$dir = strtolower($dir).'/';
		foreach($dirLevel as $folder) {
			$URLpart .= $path[$folder].'/';
		}
		if($URLpart == $dir) {
			//return 'current';
			return 'data-place="current"';
		}
	}
}

function cleanInput($input) {
 
  $search = array(
    '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
    '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
    '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
    '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
  );
 
  $output = preg_replace($search, '', $input);
  return addslashes($output);
}

function generateFormToken($form) {
	// generate a token from an unique value
	$token = md5(uniqid(microtime(), true));  
	
	// Write the generated token to the session variable to check it against the hidden field when the form is sent
	$_SESSION[$form.'_t0k3n$'] = $token; 
	
	return $token;
}

function generateWebinarID($title) {
	// generate a token from an unique value
	$token = md5($title);  
	
	return $token;
}

function verifyFormToken($form) {
    
    // check if a session is started and a token is transmitted, if not return an error
	if(!isset($_SESSION[$form.'_t0k3n$'])) { 
		return false;
    }
	
	// check if the form is sent with token in it
	if(!isset($_POST['token'])) {
		return false;
    }
	
	// compare the tokens against each other if they are still the same
	if ($_SESSION[$form.'_t0k3n$'] !== $_POST['token']) {
		return false;
    }
	
	return true;
}

function getData($url,$params){
	$curl = curl_init();
	curl_setopt_array($curl,array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_SSL_VERIFYPEER => 0,
		CURLOPT_URL => $url.$params
	));
	$resp = curl_exec($curl);
	curl_close($curl);
	return $resp;
}

function createLaunchpad($switch, $style) {
	if(!empty($_SESSION['eAuth']) && $_SESSION['eAuth'] == true) {
		$switch = 'eauth';
	}
	$data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Includes/launchpad.json'), true);
	
	if($switch != 'all') {
		
		if($switch == 'hr') {
			$header = 'Launch an HR/Payroll Application';
		} else if($switch == 'fms') {
			$header = 'Launch a Financial Application';	
		} else if($switch == 'insurance') {
			$header = 'Launch an Insurance Application';
		} else if($switch == 'eauth') {
			$header = 'Launch an eAuth Application';
		}

	}else{
		$header = 'Launch an Application';	
		
	}
	
	if($style == 'menu') {
			$launchpad = '<dl class="launchpad-launcher">';
			$launchpad .= '<dt>'.$header.'</dt>';
			foreach ($data['apps'] as $key=>$value) {
                $allClasses = '';
                $classes = explode(',',$value['classes']);
                foreach($classes as $class) {
                    $allClasses .= $class.' ';
                }
                if($switch != 'all' && strpos($value['classes'], $switch) !== false) {
                    $launchpad .=  '<dd role="menuitem" class="'.$allClasses.'tile"><a href="'.$value['url'].' target="_blank">'.$value['title'].'</a></dd>';
                }else if($switch == 'all'){
                    $launchpad .=  '<dd role="menuitem" class="'.$allClasses.'tile"><a href="'.$value['url'].' target="_blank">'.$value['title'].'</a></dd>';
                }
            }
			$launchpad .= '</dl>';
		}else{
			$launchpad = '<h3>'.$header.'</h3>';
			$launchpad .= '<ul>';
			foreach ($data['apps'] as $key=>$value) {
                $allClasses = '';
                $classes = explode(',',$value['classes']);
                foreach($classes as $class) {
                    $allClasses .= $class.' ';
                }
                if($switch != 'all' && strpos($value['classes'], $switch) !== false) {
                    $launchpad .=  '<li class="'.$allClasses.'tile"><a href="'.$value['url'].' target="_blank">'.$value['title'].'</a></li>';
                }else if($switch == 'all'){
                    $launchpad .=  '<li class="'.$allClasses.'tile"><a href="'.$value['url'].' target="_blank">'.$value['title'].'</a></li>';
                }
            }
			$launchpad .= '</ul>';
		}
		
	return $launchpad;
	
}



function featuredCourse($category = 'all') {
	$exclude = array('FMMI', '_notes');
		$videos = [];
		$folders = [];
	$dir = '\Training\Online\\';
	if($category == 'all') {
		$paths = glob($GLOBALS['doc_root'].$dir . '/*' , GLOB_ONLYDIR);
		
		for ($i = 0; $i <= count($paths) - 1; $i++) {
			$path_parts = pathinfo($paths[$i]);
			$folders[] = $path_parts['basename'];
		}
		
		do {
			$folder = $folders[array_rand($folders)];
			$subdir = $dir.$folder;
		} while (in_array($folder, $exclude));
	}else{
		$subdir = $dir.strtolower($category);
	}
	
	$html = file_get_html($GLOBALS['doc_root'].$subdir.'\index.php');
	
	foreach($html->find('main figure') as $e) {
		//break into parts
			$image =  $e->children(0)->src;
			//echo $image->src;
			$heading =  $e->children(1)->find('h4', 0);
			$paragraph = $e->children(1)->find('p', 0);
			$courseType =  $e->children(1)->find('small', 0);
			//Extract link and explode out HREF
			$link =  $e->children(1)->find('a', 0);
			$linkHREF = $link->href;
			$linkRel = $link->rel;
			
			//Rebuild course
			$course = '<figure>';
			$course .= '<img src="'.str_replace('\\', '/',$GLOBALS['base_url'].$subdir.'/'.$image).'" alt="" />';
        	$course .= '<figcaption>';
          	$course .= $heading;
			$course .= $paragraph;
          	$course .= $courseType;
			if($linkRel != 'external') {
          		$course .= '<div>'.'<a href="'.str_replace('\\', '/',$GLOBALS['base_url'].$subdir.'/'.$linkHREF).'" class="button">View Course</a></div></figcaption></figure>';
			}else{
				$course .= '<div>'.'<a href="'.$linkHREF.'" class="button">View Course</a></div></figcaption></figure>';
			}
			
			$videos[] .= $course;
	}
	
	echo $videos[array_rand($videos)];
	
}

function getCourses($category) {

	$folders = array('hrpay' => '\Training\Online\HR_Payroll\\', 'reporting' => '\Training\Online\Reporting\\', 'time' => '\Training\Online\Time_Attendance\\', 'fms' => '\Training\Online\FMS\\', 'nfc' => '\Training\Online\nfc\\', 'insurance' => '\Training\Online\Insurance\\');
	
	if(array_key_exists($category, $folders)) {
		$dir = $folders[$category];
	}else{
		$dir = '\Training\Online\\';
	}
	
	$i = 0;
	$html = file_get_html($GLOBALS['doc_root'].$dir.'\index.php');
	foreach($html->find('main figure') as $e) {
		if($i <=  2) {
			
			//break into parts
			$image =  $e->children(0)->src;
			$heading =  $e->children(1)->find('h4', 0);
			$courseType =  $e->children(1)->find('small', 0);
			//Extract link and explode out HREF
			$link =  $e->children(1)->find('a.button', 0);
			$linkHREF = $link->href;
			$linkRel = $link->rel;
			
			//Rebuild course
			echo '<figure>';
			echo '<img src="'.str_replace('\\', '/',$GLOBALS['base_url'].$dir.$image).'" alt="" />';
        	echo '<figcaption>';
          	echo $heading;
          	echo $courseType;
			if($linkRel != 'external') {
          		echo '<div>'.'<a href="'.str_replace('\\', '/',$GLOBALS['base_url'].$dir.$linkHREF).'" class="button">View Course</a></div></figcaption></figure>';
			}else{
				echo '<div>'.'<a href="'.$linkHREF.'" rel="external" class="button">View Course</a></div></figcaption></figure>';
			}
			$i++;
		}else{
			break;
		}
	}
	
}

class trainingSchedule {
	private $jsonFile;
	private $dates;
	private $today;
	private $courseCache = null;
	private $courseFlag;
	private $classes = null;
	private $courses = [];
	private $categories;
	public $groupBy;
	
	public function __construct() {
		$this->jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Training/Includes/schedule.json'), true);
		$this->dates = array_keys($this->jsonFile);
		$this->today = new DateTime();
		$this->courseFlag = false;
	}
	
	public function show($course) {

		for ($i = 0; $i <= count($this->dates) - 1; $i++) {
			$date = new DateTime($this->dates[$i]);
			if(strtolower($course) != 'all') {

				if($date > $this->today) {
					foreach($this->jsonFile[$this->dates[$i]] as $class) {
						if(isset($this->groupBy)) {
							if($class['group'] == $this->groupBy) {
								$this->courses[] .= $this->compileCourse(array($class));
								$this->courseFlag = true;
							}
						}else{
							if($class['type'] == $course) {
								$this->courses[] .= $this->compileCourse(array($class));
								$this->courseFlag = true;
							}
						}
					}

				}
				
			}elseif(strtolower($course) == 'all'){

				if($date > $this->today) {
					
					foreach($this->jsonFile[$this->dates[$i]] as $class) { 
						//echo $this->compileCourse(array($class));
						$this->courses[] .= $this->compileCourse(array($class));
						$this->courseFlag = true;
					}

				}
			}

		}
		if($this->courseFlag == false) {
			echo '<p>Sorry but we could not find any scheduled courses.</p>';
		}else{
			echo '<table class="schedule-table"><tr><th>Course</th><th>Date</th><th>Location</th>';
			foreach($this->courses as $course) {
				echo $course;
			}
			echo '</table>';
		}
		

	}
	
	public function classes($classType) {
		
		foreach($this->jsonFile as $date) {
			
			foreach($date as $class) {
				$needle = $class['type'];
				if($needle == $classType) {
					if(strtolower($class['registration']) != 'form') {
						$this->classes .= '<li><a href="http://usdanfc.acuityscheduling.com/schedule.php?appointmentType='.strtolower($class['registration']).'" rel="popup">'.$class['time'].'</a></li>';
					}else{
						$this->classes .= '<li><a href="'.$GLOBALS['base_url'].'/Training/Registration_Form.pdf'.'" target="_blank">'.$class['time'].'</a></li>';
					}
				}
			}
		}
		if(!empty($this->classes)) {
			echo '<ul>';
			echo $this->classes;
			echo '</ul>';
		}else{
			echo '<p>There are no scheduled classes for this course.</p>';
		}
	}

	
	protected function compileCourse($day) {
		$course = '<tr><td><a href="'.str_replace('\\', '/',$GLOBALS['base_url'].$day[0]['link']).'">'.$day[0]['title'].'</a></td>';
		
		$course .= '<td class="time">'.$day[0]['time'].'</td>';
		$course .= '<td>'.$day[0]['location'].'</td>';
		$course .= '</tr> ';
		
		return $course;
	}
	
	
}

function carouselImages($lob = 'all') {
	try{
		switch($lob) {
			case 'fmlob':
				$imagesDir = '/images/slides/marketing/fmlob/';
			break;
			
			case 'hrlob':
				$imagesDir = '/images/slides/marketing/hrlob/';
			break;
			
			case 'all':
			   $imagesDir = '/images/slides/marketing/';
			break;
            
            case 'careers':
                $imagesDir = '/images/slides/careers/';
			break;
			
			case 'careers/students':
                $imagesDir = '/images/slides/careers/students/';
			break;
			
			case 'careers/veterans':
                $imagesDir = '/images/slides/careers/veterans/';
			break;
		}
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($GLOBALS['doc_root'].$imagesDir)) as $filename)
		{
			$parts = pathinfo($filename);
			if(is_file($filename) && $parts['extension'] == 'jpg'){
				$filename = preg_replace("{\\\}", "/", $filename);
			   $images[] = $filename;
			}
		}
		$randomImage = $images[array_rand($images)];
		
		$size = getimagesize($randomImage, $info);
		if(isset($info['APP13'])) {
			$iptc = iptcparse($info['APP13']);
			$altText = $iptc["2#120"][0];
		}	
		
		$img = imagecreatefromjpeg($randomImage); 
		$w = imagesx($img); 
		$h = imagesy($img); 
		$rgb = imagecolorat($img, 1, 1); 
		$r = ($rgb >> 16) & 0xFF; 
		$g = ($rgb >> 8) & 0xFF; 
		$b = $rgb & 0xFF;         
		$hex = '#'.str_repeat('0',2-strlen(dechex($r))).dechex($r).str_repeat('0',2-strlen(dechex($g))).dechex($g).str_repeat('0',2-strlen(dechex($b))).dechex($b);
		
		$path_parts = pathinfo($randomImage);
		$imgChoice = substr($path_parts['filename'], 0, -2);
		if($lob == 'all' || $lob == 'careers') {
			$subfolder = basename($path_parts['dirname']);
			$url = $GLOBALS['base_url'].$imagesDir.$subfolder.'/'.$imgChoice;
		} else {
			$url = $GLOBALS['base_url'].$imagesDir.$imgChoice;
		}
		
		echo '<picture style="background-color:'.$hex.';">';
		echo '<!--[if IE 9]><video style="display: none;"><![endif]-->';
		echo '<source srcset="'.$url.'_l.jpg'.'" media="(min-width: 801px)">';
		echo '<source srcset="'.$url.'_m.jpg'.'" media="(min-width: 401px)">';
		echo '<source srcset="'.$url.'_s.jpg'.'" media="(min-width: 0px)">';
		echo '<!--[if IE 9]></video><![endif]-->';
		echo '<img srcset="'.$url.'_m.jpg'.'" alt="'.$altText.'">';
		echo '</picture>';
	}catch(Exception $e) {
		//print_r($e);
	}
    
}

function displayJobs() {
	$today = new DateTime();
	$today->setTime(0, 0, 0);
	$count = 0;
	$data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Includes/vacancy.json'), true);
	$errorMsg = '<p>We couldn\'t find any open positions right now, but please visit the USAJobs.gov site to learn more about working for the Federal Government.</p>';

	$jobs = array();
	if(!empty($data)){
	
		foreach ($data['JobData'] as $key=>$value) {
			//print_r($value['JobTitle']);
			if($count > 4) {
				break;
			}
			$readDate = new DateTime($value['EndDate']);
			if($readDate >= $today) {
				$jobs[] = '<li><a href="'.$value['ApplyOnlineURL'].'" target="blank">'.$value['JobTitle'].', '.$value['PayPlan'].'-'.$value['Series'].'-'.$value['Grade'].'</a></li>';
				$count++;
			}
		}
		
	}else{
		echo $errorMsg;
		echo '<a href="http://usajobs.gov" target="blank" class="button">Visit USAJobs.gov</a>';
	}
	
	if(empty($jobs) ) {
		echo $errorMsg;
		echo '<a href="http://usajobs.gov" target="blank" class="button">Visit USAJobs.gov</a>';
	} else{
		echo '<ul>';
		for($i=0; $i < count($jobs); $i++) {
			echo $jobs[$i];
		}
		echo '</ul>';
		echo '<a href="http://usajobs.gov" target="blank" class="button">View All Vacancies at USAJobs.gov</a>';
	}

}

class archive {
	public $lineOfBusiness;
	public $archiveType;
	public $cnArray = array();
	public $bucket = 'all';
    
    private $keywords = array('actions' => 'Customer Actions', 'operational' => 'Operational Status', 'maintenance' => 'System Maintenance', 'system' => 'System Updates', 'processing' => 'Processing Updates', 'training' => 'Training', 'events' => 'Customer Events');
	public $style = 'verbose'; //the other style is "list". Currently only applies to CNs
	protected $noMatch = '<div id="noMatch"><h4><svg class="icon" role="presentation"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#info"></use></svg>No matched results.</h4><p>We’re sorry, but there are no matches for the date range selected. Please enter a different time frame using the date selection options and try again.</p></div>';
	
	public function setBucket($newval) {
      $this->bucket = $newval;
  	}
	
	public function setStyle($newval) {
      $this->style = $newval;
  	}
	
	public function setlineOfBusiness($newval) {
      $this->lineOfBusiness = strtolower($newval);
  	}
	
	public function setType($newval) {
      $this->archiveType = strtolower($newval);
  	}
	
	public function get($startDate, $endDate) {
		switch($this->archiveType) {
			case 'publications':
				$this->getBulletins($startDate, $endDate);
			break;
			
			case 'news':
				$this->getNews($startDate, $endDate);
			break;
			
			case 'notifications':
				$this->getCNs($startDate, $endDate);
			break;
		}
		
	}
	
	public function getCNs($startDate, $endDate) {
		switch($this->lineOfBusiness) {
			case 'hr':
                $dir = '\ClientServices\HR_Payroll\Customer_Notifications\\';
            	
			break;
			
			case 'fms':
				$dir = '\ClientServices\FMS\Customer_Notifications\\';
				
			break;
			
			case 'insurance':
				$dir = '\ClientServices\Insurance\Customer_Notifications\\';
				
			break;
			
			
		
		}
				
				$startDate = new DateTime($startDate);
				$endDate = new DateTime($endDate);
				$cnArray = array();
                $allKeywords = '';
                $i = 0;
                
                $jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$dir.'cn.json'), true);
                
				foreach ($jsonFile as $date=>$entries) {
                    $entryDate = new DateTime($date);
                    if($entryDate <= $endDate && $entryDate >=$startDate) {
                        foreach($entries as $entry) {
                            
                            // Build CN Entry
                            if($this->style == 'list') {
                                $this->cnArray[] = '<li><time>'.$entryDate->format('F, n, Y').'</time><br><a href="'.str_replace('\\', '/',$GLOBALS['base_url'].$dir.$entryDate->format('Y').'/'.$entry['url']).'" target="_blank">'.$entry['title'].'</a></li>';
                                $i++;
                                if($i == 5) {
                                    break 2;
                                }
                            }else{
                                $allKeywords = '';
                                $keywords = explode(',',$entry['keywords']);
                                foreach($keywords as $tag) {
                                    $allKeywords .= '<button data-tag="'.$tag.'">'.$this->keywords[$tag].'</button> ';
                                }
                                
                                $this->cnArray[] = '<dl class="cn">'.
                                '<dt><a href="'.str_replace('\\', '/',$GLOBALS['base_url'].$dir.$entryDate->format('Y').'/'.$entry['url']).'"target="_blank">'.$entry['title'].'</a></dt>'.
                                '<dd class="time">'.$entryDate->format('F n, Y').'</dd>'.
                                '<dd class="description">'.$entry['desc'].'</dd>'.
                                '<dd class="keyword"><strong>Keyword:</strong> '.$allKeywords.'</dd></dl>';
                            }
                        }
                            
                    }
                    
                }
				
                $this->showCNs();
				
                
	}
	
	private function showCNs() {
		//var_dump($this->cnArray);
		if($this->style == 'list') {
					echo '<ul id="currentNews">';
					foreach($this->cnArray as $cn) {
						echo $cn;
					}
					echo '</ul>';
				}else if(!empty($this->cnArray)){
					foreach($this->cnArray as $cn) {
						echo $cn;
					}
				}else{
					echo $this->noMatch;
				}
	}
	
	public function getBulletins($startDate, $endDate) {
		//If this feature is requested by other groups then switch case on variable and adjust $dir_root variable to be generic.
		//$categories would also be unique to each group. HR Client Services is the model. Others groups must conform to this for the script to work.
		switch($this->lineOfBusiness) {
			case 'hr':
				$dir_root = '\Publications\HR_Payroll\\';
				$categories = ['Adm_Billings' => 'Administrative Billings and Processing', 'HR_Payroll_Processing' => 'HR and Payroll Processing', 'Manual_Pay' => 'Manual Pay Processing', 'Pub_Notes' => 'Pub Notes', 'Reporting' => 'Reporting', 'Research_Inquiry' => 'Research and Inquiry', 'Retirement_Processing' => 'Retirement Processing', 'TA_Processing' => 'T&A Processing', 'Taxes' => 'Taxes'];
				if($this->bucket != 'all') {
					$categories = [$this->bucket => $categories[$this->bucket]];
				}
			break;
			
			case 'fms':
				$dir_root = '\Publications\FMS\\';
				//NO CATEGORIES AS OF DATE
				$categories = [];
			break;
			
			case 'insurance':
				$dir_root = '\Publications\Insurance\\';
				$categories = ['dprs' => 'DPRS', 'cler' => 'CLER'];
				if($this->bucket != 'all') {
					$categories = [$this->bucket => $categories[$this->bucket]];
				}
			break;
		}
		
		$startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);
        $tableRows = array();
        $hashTable = array();
        $allKeywords = '';

        foreach($categories as $category=>$title) {
            $jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$dir_root.$category.'\bulletins.json'), true);

            foreach ($jsonFile as $date=>$entries) {
                $entryDate = new DateTime($date);
                if($entryDate <= $endDate && $entryDate >=$startDate) {
                    foreach($entries as $entry) {

                        $hashTable[$entry['url']] = $entry['title'];

                        if($this->bucket != 'all') {
                            $allKeywords = '';
                            $keywords = explode(',',$entry['keywords']);
                            foreach($keywords as $tag) {
                                $tag = ($tag !='' ? $tag : 'None');
                                $allKeywords .= '<span class="tag" data-tag="'.$tag.'">'.$tag.'</span> ';
                            }
                        }
                        if($entry['supersede'] != false) {
                            // Lookup updated bulletin by URL
                            $supersede = $entry['supersede'];
                            if(isset($hashTable[$supersede])) {
                                $supersede = '<strong>'.$entry['title'].'</strong> Superseded by:<br><a href="'.$entry['supersede'].'">'.$hashTable[$supersede].'</a>';
                                unset($allKeywords);
                            }
                            //echo $supersede;
                        }else{
                            $supersede = false;
                        }
                        $tableRows[$category][] = '<tr><td class="publication-title">'.($supersede == false ? '<a href="'.str_replace('\\', '/',$GLOBALS['base_url'].$dir_root.$category.'/'.$entry['url']).'" rel="popup">'.$entry['title'].'</a>' : $supersede).(isset($allKeywords) ? '<div><span class="tag-label">Sort by Tags:</span></div>'.$allKeywords : false).'</td><td class="publication-date">'.$entryDate->format('F n, Y').'</td></tr>';
                    }

                }

            } // End ForEach to process category
        }
        
        if(!empty($tableRows)) {
            foreach($tableRows as $tableKey=>$table) {

                if($this->bucket == 'all'){
                    $tableBlock = '<table class="publications infoBox"><tr><th align="left"><a href="'.str_replace('\\', '/',$GLOBALS['base_url'].'/Publications/HR_Payroll/'.$tableKey.'/').'">'.$categories[$tableKey].'</a></th><th>Date</th></tr>';
                }else{
                    $tableBlock = '<table id="bulletins" class="publications"><tr><th align="left">Bulletin</th><th>Date</th></tr>';
                }
                foreach($table as $row) {
                    $tableBlock .= $row;
                }
                $tableBlock .= '</table>';
                echo $tableBlock;
            }
        }else{
            echo $this->noMatch;
        }
		
    }
	
	public function getNews($startDate, $endDate) {

				$dir = '\News\\';

				
				$startDate = new DateTime($startDate);
				$endDate = new DateTime($endDate);
				$newsArray = array();
                $i = 0;
				
				
				$jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].$dir.'news.json'), true);
                foreach ($jsonFile as $date=>$entries) {
                    $entryDate = new DateTime($date);
                    if($entryDate <= $endDate && $entryDate >=$startDate) {
                        foreach($entries as $entry) {
                            
                            // Build News Entry
                            if($this->style == 'list') {
                                
                                $newsArray[] = '<li><time>'.$entryDate->format('F, n, Y').'</time><br><h4>'.$entry['title'].'</h4>'.'<p>'.$entry['desc'].($entry['url'] != false ? '<a href="'.$entry['url'].'" target="_blank">'.$entry['linkText'].'</a>' : false).'</li>';
                                $i++;
                                if($i == 3) {
                                    break 2;
                                }
                            }else{
                                
                                $newsArray[] = '<dl class="cn">'.
                                '<dt>'.$entry['title'].'</dt>'.
                                '<dd class="time">'.$entryDate->format('F n, Y').'</dd>'.
                                '<dd class="description">'.$entry['desc'].($entry['url'] != false ? '<a href="'.$entry['url'].'" target="_blank">'.$entry['linkText'].'</a>' : false).'</dd></dl>';
                            }
                        }
                            
                    }
                    
                }
				
				
				
				if($this->style == 'list') {
					echo '<ul id="currentNews">';
					foreach($newsArray as $news) {
						echo $news;
					}
					echo '</ul>';
				}else if(!empty($newsArray)){
					foreach($newsArray as $news) {
						echo $news;
					}
				}else{
					echo $this->noMatch;
				}

	}
}


function draw_calendar($month,$year, $lob, $filter='none'){
	
	/*Get Today's Date*/
	$today = date('m-j-Y');
	
	//*Get Events Files*/
	switch($lob){
		case 'hrlob':
			$jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Includes/calendar-HR.json'), true);
		break;
		
		case 'fmlob':
			$jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Includes/calendar-FMS.json'), true);
		break;
		
		case 'ezfedgrants':
			$jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Includes/calendar-ezFedGrants.json'), true);
		break;
		
		case 'training':
			$jsonFile = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/Training/Includes/schedule.json'), true);
		break;
	}
	foreach ($jsonFile as $events=>$event) {
        $calendarEvents[] = $events;
        foreach($event as $value) {
            $types[] = $value['type'];
        }
        $eventTypes[$events] = $types;
        unset($types);
	}
	
	/*Define Calendar Header*/
    $month = ($month == 13 ? 1 : $month);
	$dateObj   = DateTime::createFromFormat('!m', $month);
	$monthName = $dateObj->format('F');
	$calendar = '<h3 id="currentMonth" class="'.$lob.'" data-date="'.$month.'-'.$year.'" aria-live="polite" aria-relevant="text">'.$monthName.' '.$year.'</h3>';
	
	/*Calendar controls*/
	//$calendar .= '<span id="previousMonth" tabindex="0" onclick="calendar(\'previous\');">Previous Month</span>';
	//$calendar .= '<span id="nextMonth" tabindex="0" onclick="calendar(\'next\');">Next Month</span>';
	//$calendar .= '<span id="closeEvents" onclick="calendar(\'close\');">Close</span>';
	
	$calendar .= '<button id="previousMonth" onclick="calendar(\'previous\');">Previous Month</button>'; //aria-controls="calendar"
	$calendar .= '<button id="nextMonth" onclick="calendar(\'next\');">Next Month</button>'; //aria-controls="calendar"
	
	/* draw table */
	$calendar .= '<table cellpadding="0" cellspacing="0" class="calendar">';

	/* table headings */
	$headings = array('SUNDAY','MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY');
	
    $calendar.= '<tr class="calendar-row">';
    for ($i = 0; $i <= count($headings)-1; $i++) {
        $calendar.= '<th class="calendar-day-head" title="'.$headings[$i].'">'.substr($headings[$i], 0, 2).'<span>'.substr($headings[$i], 2, 9).'</span></th>';
    }
    $calendar.= '</tr>';
    

	/* days and weeks vars now ... */
	$running_day = date('w',mktime(0,0,0,$month,1,$year));
	$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
	$days_in_this_week = 1;
	$day_counter = 0;
	$dates_array = array();

	/* row for week one */
	$calendar.= '<tr class="calendar-row">';

	/* print "blank" days until the first of the current week */
	for($x = 0; $x < $running_day; $x++):
		$calendar.= '<td class="calendar-day-np"> </td>';
		$days_in_this_week++;
	endfor;

	/* keep going with days.... */
	for($list_day = 1; $list_day <= $days_in_month; $list_day++):
        //$curDate = date($month.'-'.$list_day.'-'.$year);
		$curDate = date("Y-m-d", strtotime($year.'-'.$month.'-'.$list_day));
		$readDate = date("F d, Y", strtotime($curDate));
        if (in_array($curDate, $calendarEvents)) {
            if(in_array($filter, $eventTypes[$curDate])) {
                $calendar .= ($curDate != $today ? '<td class="calendar-day event '.$filter.'">' : '<td class="calendar-day-today event '.$filter.'">');
            }else if($filter == 'none'){
                $calendar .= ($curDate != $today ? '<td class="calendar-day has-events">' : '<td class="calendar-day-today has-events">');
            }else{
                $calendar .= ($curDate != $today ? '<td class="calendar-day">' : '<td title="'.$readDate.'" class="calendar-day-today">');
            }
			/* add in the day number */
			$calendar.= '<a title="'.$readDate.'" href="#'.strtotime($readDate).'" aria-haspopup="true" onclick="calendar(\'open\');">'.$list_day.'</a>';
            /** QUERY THE DATABASE FOR AN ENTRY FOR THIS DAY !!  IF MATCHES FOUND, PRINT THEM !! **/
            $count = sizeof($jsonFile[$curDate]) - 1;
            $calendar .= '<div id="'.strtotime($readDate).'" class="eventContainer" aria-hidden="true"><dl><dt>Events</dt>';
			for ($i = 0; $i <= $count; $i++) {
                $type = $jsonFile[$curDate][$i]['type'];
				switch($filter) {
						case 'pay':
							if($type == 'pay') {
								$calendar .= '<dd class="pay"><strong>'.$jsonFile[$curDate][$i]['title'].'</strong></dd>';
							}
						break;
						case 'maintenance':
							if($type == 'maintenance') {
								$calendar .= '<dd class="maintenance"><strong>'.$jsonFile[$curDate][$i]['title'].'</strong>';
								if(!empty($jsonFile[$curDate][$i]['desc'])) {
									$calendar .= '<br>'.$jsonFile[$curDate][$i]['desc'].'</dd>';
								}else{
									$calendar .= '</dd>';
								}
							}
						break;
						case 'customer':
							if($type == 'customer') {
								$calendar .= '<dd class="customer"><strong>'.$jsonFile[$curDate][$i]['title'].'</strong>';
								$calendar .= ' - <i>'.$jsonFile[$curDate][$i]['time'].' at '.$jsonFile[$curDate][$i]['location'].'</i>';
								if(!empty($jsonFile[$curDate][$i]['desc'])) {
									$calendar .= '<br>'.$jsonFile[$curDate][$i]['desc'].'</dd>';
								}else{
									$calendar .= '</dd>';
								}
							}
						break;
						default:
						
						if($filter == $type) {
							$calendar .= '<dd class="'.$type.'"><strong>'.$jsonFile[$curDate][$i]['title'].'</strong>';
							if(!empty($jsonFile[$curDate][$i]['time']) && !empty($jsonFile[$curDate][$i]['location'])) {
							  $calendar .= ' - <i>'.$jsonFile[$curDate][$i]['time'].' at '.$jsonFile[$curDate][$i]['location'].'</i>';
							}
							if(!empty($jsonFile[$curDate][$i]['desc'])) {
								$calendar .= '<br>'.$jsonFile[$curDate][$i]['desc'].'</dd>';
							}else{
								$calendar .= '</dd>';
							}
						} else if ($filter == 'none') {
							$calendar .= '<dd class="'.$type.'"><strong>'.$jsonFile[$curDate][$i]['title'].'</strong>';
							if(!empty($jsonFile[$curDate][$i]['time']) && !empty($jsonFile[$curDate][$i]['location'])) {
							  $calendar .= ' - <i>'.$jsonFile[$curDate][$i]['time'].' at '.$jsonFile[$curDate][$i]['location'].'</i>';
							}
							if(!empty($jsonFile[$curDate][$i]['desc'])) {
								$calendar .= '<br>'.$jsonFile[$curDate][$i]['desc'].'</dd>';
							}else{
								$calendar .= '</dd>';
							}
						}
						break;
				}
            }
            $calendar .= '</dl>';
            $calendar .= '<span class="closeEvents" onclick="calendar(\'close\');">Close Event</span></div>';
        }else{
            $curDate = date($month.'-'.$list_day.'-'.$year);
            $calendar .= ($curDate != $today ? '<td title="'.$readDate.'" class="calendar-day">' : '<td title="'.$readDate.'" class="calendar-day-today">');
            /* add in the day number */
            $calendar.= '<span title="'.$readDate.'">'.$list_day.'</span>';
        }
        $calendar.= '</td>';
    
    
		if($running_day == 6):
			$calendar.= '</tr>';
			if(($day_counter+1) != $days_in_month):
				$calendar.= '<tr class="calendar-row">';
			endif;
			$running_day = -1;
			$days_in_this_week = 0;
		endif;
		$days_in_this_week++; $running_day++; $day_counter++;
	endfor;

	/* finish the rest of the days in the week */
	if($days_in_this_week < 8):
		for($x = 1; $x <= (8 - $days_in_this_week); $x++):
			$calendar.= '<td class="calendar-day-np"> </td>';
		endfor;
	endif;

	/* final row */
	$calendar.= '</tr>';

	/* end the table */
	$calendar.= '</table>';
	
	/* all done, return result */
	return $calendar;
}

?>
