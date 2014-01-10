<?php
fclose(STDIN);
fclose(STDOUT);
fclose(STDERR);

$STDIN = fopen('/dev/null', 'r');
$STDOUT = fopen(dirname(__FILE__) . '/application.log', 'wb');
$STDERR = fopen(dirname(__FILE__) . '/error.log', 'wb');


include_once("simple_html_dom.php");
include_once("PointLocation.php");
$filename = "post_ids.txt";
$craigslist_url = "http://losangeles.craigslist.org";

// Create DOM from URL
$html = file_get_html("http://losangeles.craigslist.org/search/apa/lac?catAbb=apa&query=&zoomToPosting=&minAsk=&maxAsk=1300&bedrooms=1&sort=date&housing_type=#grid");

//search polygon
$polygon = array("33.995039 -118.395565", "34.005001 -118.420799", "34.033599 -118.431957", "34.037582 -118.40123", "34.055362 -118.376682", "34.047824 -118.359001", "34.036586 -118.354366", "34.020226 -118.372048", "33.995039 -118.395565");
//Init array of postings
$postings = array();

/*
* LOGIC:
	we open file and store all the ids in an array 
	we crawl the first 100 items on the page
	for every match inside the bounds 
	we try to find a match in the array 
	if its not there we add that element to the array
	and we send an email 
	at the end we save back all the ids into the same file
*
*/

$previous_postings = array();
if (file_exists($filename)) {
	//OPEN FILE
	$previous_postings = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

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
		echo "\n Within bounds " . $post['href'];
		$valid_postings[] = $post;
	} else {
		echo "\n Outside bounds " . $post['href'];
	}
}


//Mail
// multiple recipients
$to  = 'antoineb19+housingsearch@gmail.com' ; 

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
	      <td><img src="'.$posting['img'].'"/></td><td>'.$posting['href'].'</td><td>'.$posting['price'].'</td>
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




//Append the valid postings to the file

// The file pointer is at the bottom of the file 
if (!$handle = fopen($filename, 'a+')) {
     echo "\n Cannot open file ($filename)";
     exit;
} else {

	foreach ($valid_postings as $key => $posting) {
		$line = $posting['id'] . PHP_EOL;

	    // Write $somecontent to our opened file.
	    if (fwrite($handle, $line) === FALSE) {
	        echo "\n Cannot write to file ($filename)";
	        exit;
	    }
	}

	echo "\n Success, wrote to file ($filename)";

	fclose($handle);
}






