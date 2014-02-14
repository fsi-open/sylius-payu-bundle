# Installation

## 1. Download Bundle

Add to composer.json

```
"require": {
    "fsi/sylius-payu-bundle": "1.0.*@dev"
}
```

## 2. Register bundle

```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new FSi\Bundle\SyliusPayuBundle\SyliusPayuBundle()
    );
}
```

## 3. Configure application

```yml
# app/config/parameters.yml

payu.key1: EDITME
payu.key2: EDITME
payu.pos_id: EDITME
payu.pos_auth_key: EDITME
payu.sandbox: true # false on production
```

```yml
# app/config/payum.yml
payum:
    security:
        token_storage:
            Sylius\Bundle\PayumBundle\Model\PaymentSecurityToken:
                doctrine:
                    driver: orm
    contexts:
        # some other contexts
        payu:
            payu:
                api:
                    options:
                        key1:         %payu.key1%
                        key2:         %payu.key2%
                        pos_id:       %payu.pos_id%
                        pos_auth_key: %payu.pos_auth_key%
                        sandbox:      %payu.sandbox%
                actions:
                    - sylius.payum.action.order_status
                    - sylius.payum.action.execute_same_request_with_payment_details
            storages:
                Sylius\Bundle\CoreBundle\Model\Order:
                    doctrine:
                        driver: orm
                Sylius\Bundle\PaymentsBundle\Model\Payment:
                    doctrine:
                        driver: orm
        # some other contexts
```

## 4. Register routes

```
# app/config/routing.yml

sylius_payu_capture:
    resource: "@SyliusPayuBundle/Resources/config/routing/capture.xml"
```

## 5. Add payment method to database

Execute ``php app/console doctrine:fixtures:load``