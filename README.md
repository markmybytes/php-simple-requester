# php-simple-requester
 A simple PHP cURL warpper
 
 ## usage
```php
$response = HTTPRequester::init("https://api.ipify.org")
    ->setQuery(['format' => "json"])
    ->get()
    ->jsonResponse();
// ["ip" => "192.168.50.1"]
```
