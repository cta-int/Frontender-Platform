<?php

// Check for the env Variables.
if (!isset($_ENV['MONGO_HOST'])) {
    return false;
}

if (!isset($_ENV['MONGO_DB'])) {
    return false;
}

if (!isset($_ENV['FEP_TOKEN_SECRET'])) {
    return false;
}

if (!isset($_ENV['FEP_TOKEN_HEADER'])) {
    return false;
}

if (!isset($_ENV['ENV'])) {
    return false;
}

if (!isset($_ENV['CUSTOMER_KEY'])) {
    return false;
}

if (!isset($_ENV['CUSTOMER_SECRET'])) {
    return false;
}

if (!isset($_ENV['TOKEN'])) {
    return false;
}

if (!isset($_ENV['TOKEN_SECRET'])) {
    return false;
}

if (!isset($_ENV['SCR_TOKEN'])) {
    return false;
}

return true;