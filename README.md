## craigslist-crawler

### Description

Hello,

Have you ever searched for anything on  &copy; [Craigslist](http://www.craigslist.com)? 
If yes, then you probably already told yourself:

**I would like to be immediately notified when there is a new posting matching my needs and located within a specific geographic area.**

Would come in handy, right?
Well, this script provides that feature. Whenever there is a new post it just sends you an email with the post attached so you can be the first to jump on the listing.


### Requirements

php > 5.3 installed

### Usage

Run the `crawler.php` script by command line for testing.
   
   `php crawler.php <emails> <query> <polygon>`
   
(Recommended) Add this command to a cron:

   `0 */5 * * * php [PATH_TO_FILE]/crawler.php <emails> <query> <polygon>`

   + Where `<emails>` is a comma delitmited list of valid emails
   + Where `<query>` is your craigslist search query (see [query](#2))
   + Where `<polygon>` is a comma delimited list of longitude and latitude forming a closed polygon (see [polygon](#3))

#### Parameters

- `<query>` :<a name="2"></a> Perform a regular search on craigslist with the desired filters. Then copy the URL to your clipboard.

   e.g: 'http://losangeles.craigslist.org/search/apa?catAbb=apa&maxAsk=1500&sort=date#grid'
   
- `<polygon>` :<a name="3"></a> Go to [Google Maps](http://maps.google.com) and define the bounds of your polygon. (To get the latitude and logintude of any point just drop a pin on the map, right-click and select "What's here")

   e.g:  '33.995039 -118.395565,34.005001 -118.420799,34.033599 -118.431957,34.037582 -118.40123,34.055362 -118.376682,34.047824 -118.359001,34.036586 -118.354366,34.020226 -118.372048,33.995039 -118.395565'


### Example Usage 

   `php crawler.php 'SOMEEMAILADDRESS@gmail.com' 'http://losangeles.craigslist.org/search/apa?catAbb=apa&maxAsk=1500&sort=date#grid' '33.995039 -118.395565,34.005001 -118.420799,34.033599 -118.431957,34.037582 -118.40123,34.055362 -118.376682,34.047824 -118.359001,34.036586 -118.354366,34.020226 -118.372048,33.995039 -118.395565'`

## Past postings

This script works at sending alerts for *future* posts. 
Since postings usually have a very short life span, this script only process postings back up to the most recent 100.

## Warning

This software is provided as is.
Please check &copy; [Craigslist](http://www.craigslist.com) [terms and conditions](http://www.craigslist.org/about/terms.of.use) related to the data you extract.

## License

Licensed under the MIT License
