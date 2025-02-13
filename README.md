# php-simple-requester
 A simple PHP cURL warpper
 
 ## usage
```php
// get request
$response = HTTPRequester::init("https://api.ipify.org")
    ->setQuery(['format' => "json"])
    ->get()
    ->jsonResponse(); // ["ip" => "192.168.1.1"]
```
```php
// post request
$requester = HTTPRequester::init("https://post-to-this-site.com")
    ->setHeader(['Content-Type' => "application/json])
    ->setJsonPayload(['foo' => 'bar'])
    ->post();

$response->jsonResponse(); // ['success' => 1, 'data' => [...]]
$response->url(); // https://post-to-this-site.com
$response->remoteIp(); // 12.34.56.78
```
