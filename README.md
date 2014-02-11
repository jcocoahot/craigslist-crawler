## craigslist-crawler
=====================

### Usage
=========

1. Perform a regular search on craigslist with the desired filters.
   Then copy the URL to your clipboard, for example my search query is 'http://losangeles.craigslist.org/search/apa?catAbb=apa&maxAsk=1500&sort=date#grid'
2. Go to [Google Maps](http://maps.google.com) and define the bounds of your polygon. (To get the latitude and logintude of any point just drop a pin on the map, right-click and select "What's here")
   For example my bounding polygon is '33.995039 -118.395565,34.005001 -118.420799,34.033599 -118.431957,34.037582 -118.40123,34.055362 -118.376682,34.047824 -118.359001,34.036586 -118.354366,34.020226 -118.372048,33.995039 -118.395565'
3. Finally just run the `crawler.php` script either by command line or insde a cron
   For example my command is `php crawler.php 'SOMEEMAILADDRESS@gmail.com' 'http://losangeles.craigslist.org/search/apa?catAbb=apa&maxAsk=1500&sort=date#grid' '33.995039 -118.395565,34.005001 -118.420799,34.033599 -118.431957,34.037582 -118.40123,34.055362 -118.376682,34.047824 -118.359001,34.036586 -118.354366,34.020226 -118.372048,33.995039 -118.395565'`
