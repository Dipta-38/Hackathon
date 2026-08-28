<?php

return [

    'paths' => [
        resource_path("views"),
        base_path("app/Modules/Auth/Views"),
    ],

    'compiled' => env(
        "VIEW_COMPILED_PATH",
        realpath(storage_path("framework/views"))
    ),

];
