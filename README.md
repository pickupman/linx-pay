# LinxPAY

**PHP client library to make API requests for LinxPAY system.**

## Installation

```json
    composer require pickupman/linx-pay
```

## Usage

This library will handle the OAuth2 flow for retrieving and maintaing an access token.

### Intialize the the class

```php
    $options = [
        'username' => 'Your Username',
        'password' => 'Your Password',
        'client_id' => 'Your API Client ID',
        'client_secret' => 'Your API Client Secret'
    ];

    $linx = new Pickupman\LinxPay($options);
```


### Poll API call

```php
    $poll = $linx->poll();
    var_dump($poll); // JSON response object {'success' : true }
```

### Redemption API call

```php
    $redemption = $linx->redemption([
        'linx_card_number' => '1234567890123456',
        'customer' => [
            'type'      => 'drivers_license',
            'name'      => 'John Smith',
            'id_number' => '1234', // drivers license number
            'state'     => 'Colorado' // drivers license State
        ],
        'product_type' => 'medicinal', // or recreational
        'store_location' => [
            'name' => 'Dispensary Name'
        ],
        'budtender' => [
            'name' => 'David Smith'
        ],
        'amount' => '200.00'
    ]);

    var_dump($redemption); // JSON response object
```