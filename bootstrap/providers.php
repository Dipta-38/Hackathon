<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Modules\Auth\Providers\AuthServiceProvider::class,
    App\Modules\Account\Providers\AccountServiceProvider::class,
    App\Modules\Transaction\Providers\TransactionServiceProvider::class,
    App\Modules\Request\Providers\RequestServiceProvider::class,
];