<?php

// This file shows only what to ADD to your existing config/services.php
// Merge the 'anthropic' block into your existing return array

return [

    // ... your other services (mailgun, postmark, ses, etc.) ...

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

];
