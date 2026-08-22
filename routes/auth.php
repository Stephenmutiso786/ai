<?php

// Authentication (login, registration, password reset, email verification)
// is intentionally NOT hand-rolled here. For a platform that will hold
// financial account connections, install Laravel Fortify or Breeze and
// layer 2FA on top before going further:
//
//   composer require laravel/fortify
//   php artisan fortify:install
//
// Hand-written auth is one of the most common sources of real security
// bugs in projects like this one; there's no good reason to reinvent it.
