<?php
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);

$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen(dirname(__FILE__) . '/application.log', 'wb');
$STDERR = fopen(dirname(__FILE__) . '/error.log', 'wb');

//error log func
function err($str) {
	global $STDERR;
	fwrite($STDERR, "\n".$str); 
}

require_once("simple_html_dom.php");
require_once("PointLocation.php");
$filename = dirname(__FILE__) . "/post_ids.txt";

//Checking CLI
if (PHP_SAPI != "cli" && PHP_SAPI != "cgi-fcgi") {
    err("This script must be ran from the command line. Exiting...");
    exit(1);
}

//Validation

if ($argc < 4) {
	err("Wrong number of arguments.");
	err("Usage php crawler.php {emails} {search query} {bounding polygon} ");
	err("e.g: php crawler.php 'antoineb19+housingsearch@gmail.com'".
			" 'http://losangeles.craigslist.org/search/apa?catAbb=apa&maxAsk=1500&sort=date#grid'".
			" '33.995039 -118.395565,34.005001 -118.420799,34.033599 -118.431957,34.037582 -118.40123,34.055362 -118.376682,34.047824 -118.359001,34.036586 -118.354366,34.020226 -118.372048,33.995039 -118.395565'");
	exit(1);
} 

if (!$argv) {
	err("Error occured with the arguments");
	exit(1);
}

$emails = $argv[1];
$search_query = $argv[2];
$bounding_polygon = $argv[3];

if (filter_var($search_query, FILTER_VALIDATE_URL) == false) {
	err("Invalid search query, please enter a valid url");
	exit(1);
}

$emails_arr = explode(",",$emails);
foreach ($emails_arr as $email) {
	if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
		err("Invalid email, please enter a valid email for ". $email);
		exit(1);
	}
}

$bounding_polygon_arr = explode(",", $bounding_polygon);
if (count($bounding_polygon_arr) < 3) {
	err("The bounding polygon must be a polygon...");
	exit(1);
}
if ($bounding_polygon_arr[0] != end($bounding_polygon_arr)) {
	err("Please make sure the bouding polygon is closed.");
	exit(1);
}


//Start


/*
* LOGIC:
	1.we open/read text file and store all the ids in an array 
	2.we crawl the first 100 items on the page
	  for every match inside the bounds 
	  we try to find a match in the array 
	  if its not there we add that element to our results
	3. we mail the results 
	4. and at the end we save back all the ids into the file
*
*/


$url = parse_url($search_query);
$host = $url['host'];
//search polygon
$polygon = $bounding_polygon_arr;
//Init array of postings
$postings = array();



//1.

$previous_postings = array();
if (file_exists($filename)) {
	//OPEN FILE
	$previous_postings = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

//2.

// Create DOM from URL
$html = file_get_html($search_query);
// Search all p tags  
foreach($html->find('p') as $element) {
	//check if the element has already been processed
	if (isset($element->{'data-pid'})) {
		if (in_array($element->{'data-pid'}, $previous_postings)) {
			echo "\n" . $element->{'data-pid'} . " has already been processed";
			continue;
		}
	} else {
		echo "\n data-pid is not isset";
		continue;
	}


	if (isset($element->{'data-latitude'}) && isset($element->{'data-latitude'})) {
		$new_posting = array();
		$new_posting['latitude'] = $element->{'data-latitude'};
		$new_posting['longitude'] = $element->{'data-longitude'};
		$new_posting['id'] = $element->{'data-pid'};
		
		//extract title of the posting (2nd a element inside the p)
		if ($element->find('a', 1)) {
			$new_posting['title'] = $element->find('a', 1)->innertext;
		}

		//extract price of the posting ())
		if ($element->find('span.price', 0)) {
			$new_posting['price'] = $element->find('span.price', 0)->innertext;
		}

		//inside or p post tag , search for the links that have the class=i
		foreach ($element->find('a.i') as $a) {
			if (isset($a->href)) {
				$new_posting['href'] = $craigslist_url . $a->href;
			} 

			if (isset($a->{'data-id'})) {
				$new_posting['img'] = "http://images.craigslist.org/" . str_replace('0:', '', $a->{'data-id'}) . "_300x300.jpg";
			}
		}
		$postings[] = $new_posting;
	}
}

$valid_postings = array();
//Once we have the new postings
//Let's check if there are within the bounds of our perimeter
$pointLocation = new pointLocation();
foreach ($postings as $post) {
	$point = $post['latitude'] . " " . $post['longitude'];
	if ($pointLocation->pointInPolygon($point, $polygon) != "outside") {
		echo "\n Within bounds " . $post['href']. " - latitude: ". $post['latitude'] . "  longitude: ". $post['longitude'];
		$valid_postings[] = $post;
	} else {
		echo "\n Outside bounds " . $post['href']. " - latitude: ". $post['latitude'] . "  longitude: ". $post['longitude'];
	}
}


//3.

//Mail the results 
// for multiple recipients, its comma separated
$to  = $emails ; 

foreach ($valid_postings as $posting) {
	// subject
	$subject = 'CRAIGSLIST SCRIPT: ' . $posting['title'];

	// message
	$message = '
	<html>
	<head>
	  <title>'.$posting['title'].'</title>
	</head>
	<body>
	  <p>'.$posting['title'].'</p>
	  <table>
	    <tr>
	      <th>Picture</th><th>Link</th><th>Price</th>
	    </tr>
	    <tr>
	      <td><img src="'.$posting['img'].'"/></td><td><a href="http://'.$host.$posting['href'].'">link</a></td><td>'.$posting['price'].'</td>
	    </tr>
	  </table>
	</body>
	</html>
	';

	// To send HTML mail, the Content-type header must be set
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

	// Mail it
	mail($to, $subject, $message, $headers);
}


//4.

//Append the valid postings to the file
// The file pointer is at the bottom of the file 
if (!$handle = fopen($filename, 'a+')) {
     err("Cannot open file ($filename)");
     exit(2);
} else {

	foreach ($valid_postings as $key => $posting) {
		$line = $posting['id'] . PHP_EOL;

	    // Write $somecontent to our opened file.
	    if (fwrite($handle, $line) === FALSE) {
	        err("Cannot write to file ($filename)");
	        exit(2);
	    }
	}

	echo "\n Success, wrote to file ($filename)";

	fclose($handle);
}