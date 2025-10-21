<?php

// Application Configuration Settings

return [
    'session_timeout_seconds' => 1800,
    'session_warning_seconds' => 60,
    
    // --- NEW LOGIN LOCKOUT SETTINGS ---
    'login_max_attempts' => 5,       // Attempts allowed
    'login_lockout_minutes' => 10,   // Lockout duration
];