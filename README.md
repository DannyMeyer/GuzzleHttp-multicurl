# GuzzleHttp-multicurl
A helper for easy asynchronous multicurl requests with GuzzleHttp

**Usage**
```php
$curl = new \DannyMeyer\Curl\Multicurl();

$curl->addGetRequestByUri('https://my.domain/ExampleRequest');
$curl->addGetRequestByUri('https://my.domain/AnotherExampleRequest');

$result = $curl->execute();

if ($curl->hasErrors()) {
    var_dump($curl->getErrors());
}
```

**Add Request with Authentication**
```php
$curl = new \DannyMeyer\Curl\Multicurl();
$request = new \GuzzleHttp\Psr7\Request(
    'get',
    'https://my.domain/ExampleRequest',
    ['Authorization' => 'Basic ' . \base64_encode('User:Password')]
);

$curl->addRequest($request);
```