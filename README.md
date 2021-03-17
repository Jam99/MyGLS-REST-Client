# MyGLS-REST-Client
MyGLS REST Client is a PHP Class that helps to work with MyGLS API.

### Code examples (with fake credentials):
#### Creating client instance:
```php
try{
    $wcgls_client = new GLS_REST_Client([
        "client_number" => 123456
        "username"      => "john.doe@example.com",
        "password"      => "123456",
        "country"       => "Hungary",
        "test_client"   => true
    ]);
}
catch (Exception $e){
    echo $e->getMessage();
}
```

#### Request to PrintLabels endpoint:
```php
try{
    $args = [
        "parcel_list" => ARRAY_OF_PARCELS_HERE, //read API documentation to understand Parcel class (https://api.mygls.hu/docs/mygls_api_20201105.pdf)
        "print_position" => 1
        "show_print_dialog"=> false
    ];
    $wcgls_client->PrintLabels($args)
}
catch (Exception $e){
    echo $e->getMessage();
}
```
