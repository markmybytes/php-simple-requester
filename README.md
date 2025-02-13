# php-simple-requester
 A simple PHP cURL warpper
 
 ## usage
```php
// get request
$response = HTTPRequester::init("https://api.ipify.org")
    ->setQuery(['format' => "json"])
    ->get()
    ->jsonResponse();

// post request
$requester = HTTPRequester::init("https://post-to-this-site.com")
    ->setHeader(['Content-Type' => "application/json])
    ->setJsonPayload(['foo' => 'bar'])
    ->post();

$requester->jsonResponse();
$requester->url();
$requester->remoteIp();
```
