<?php

return [
    'alipay' => [
        'app_id'         => '2016091800542197',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1YQw/RzqOVQHTVCy+NKruKxMyxyglKGGoOdzPoV+14rjDtuLU6N9QlkqkAAuCzkgt+5tWeU0PprlGGOz+K7XiQiKY7+odyhQBnWUEJSXQZET4vAufLjg+3fh5KhPoi2kRjGDIo2ldQdYqeYWne1qNiZS9v0IN7KyX3pxCEMOISrT01fy7w9fuevaYhWpLObDWH7hAgFLF2hUtTcn26obnUi0/X+rwRaynBeZA69iDmEWK3cx35Ew859KXMNW5lbjJ2NUldjTEn0BnYEirdVA/X33otiJHuoqAb3DGDumo+4hba84gXGJXYItyoaWavYaDBcF1rFp2UkX/y3j5q3SLQIDAQAB',
        'private_key'    => 'MIIEowIBAAKCAQEAmiZW31TRFHi+sOVMqaILnp/SBlAXOTD2iTIxq8C8z9vD5udGtNeibhcWVeoIg0vUSCBNT4T6K6p+iMwFS8jZBti29SgfLKMHwKfxgPuFns9Zvyu1KXCCJmi5r1UtmBUTcu9r5Gy1Lu1elALXvaeguJYUw/SUTnxirbdQRrWTq5UFi4FZVmJBBtx4SYeDpZJCvaV5vgzwWlEjadaIahXHlHW2uPNJc+8ySOZ4dazx84Q/uOMiZa0ue7iaUBVCtyYCQmIOCaFPiNjuchUwIpboExKF22p660ab4bzD/rs4xXTG8pSCFWeg64LxOaKSo84gRKs5Tk6pL1+RjIZ4sorfJQIDAQABAoIBAQCBSvEIh0KK8i+3fcxMZ79pP9HQO7MO5DOUpYYl8hYsHb9117xliIwT3Md+LjsPrTWTfYx6yyVuJKXXJZC3nPHznSfv/sQPE3s4K2UT82OC7ZXwE7/yzwxLHQHJvx7r9ZmHiKutP0TE5JG0gaHk0EmtKSyno2gVYJz/6caqWDhQ6/L4rikHPZGDr7UlykN8ZTEykxAh9YBuPXDW/6zu3c6rXq1vK2pHHrRaFELXcHr2aTiZFQqIKJrj6mbmp+5eISLvf2OOGVbKw5Js3mj18uZKiTOu1JiPSXZhfrs4durBVYf9jBX3UxaMEEEEqPgP7QS/fh8WtMwwfTjxOxZPi+WhAoGBAM2CGjUf2NhtK0+F98BY/DWeM/cu/E4wUB/Ih+jVxgD0o9+pqV4/l4OPmHsqAHzPt8lgvCDkH0WoooHogPwkdEQ9isDBre1QFBqml2X1pXoUttDSBVFvpiscy5wX7M9Tt2WkyNOXs+IaoT+TteQG4U4OI7JvwFRpxOYO30JhxfHNAoGBAMAF7sTYpmtUCdGNKfXkP5aPyX/Qz9wWJXmMMGmgztqE1s43amaDuCrmJBpsRbkikX4/A7+M4BIv5wC8kZVuv3d1tyFBKmwxOPi7x2IWDJDdTKGCUAkJdfM1IMk7/9Bq9lX1aokJQQVvwwyj7TpvimOCeqk4sP60v3poJD2eoaq5AoGAXXex0b2lU3H7g9SrhOkz9HL6sY7GJA2nM31yCvzHbIHX0o8IwBvuHmD9+e51gE69CTMc/VyjuHvlRW5o08UEuFDBPcAZCdkk+6YPL8lUtLydFUagMpS0H+Gd3WWoR/eMbwdOa6YQo2J3OkJFNOvIJ0cZBzv6XqooanYTJs7vR3UCgYApx2Cq3YRf57n3KOKPSGj5ZjFuKM2A0iS3LGRAJye6bZqa5X217kS8uLP0N8h3vmivciBrpcf+zQNSqrR2MdFguXDtwFLtlUwS+jAoTCnRvwR0Q3kMbk2Ga/HFNtOy0gLksu/J/wRxnV5vup7bzvbqLbJobRg9YrAiftQbwA7mqQKBgF0m2MOCDX8ZtdOuJOFnNKoBtngF5VGNC8j5PMSCiQszac6Cy2t0TffgZ7AkX9gw3V3g909sSSjrUgVc/x7Yp20XZ0qT0t56TGIHNO+tMrlrbabH7vcpq1xgd2HKkROkPxB9ODzjyIaaRc4Hp8jR/GzNfUSX7z873uCRSYlA32yW',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];