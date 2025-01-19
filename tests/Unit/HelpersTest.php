<?php

use ToneflixCode\LaravelFileable\Initiator;

it('generates a string matching the pattern [A-Za-z0-9]{5}-[0-9]{3}-[A-Za-z0-9]{8}', function () {
    $pattern = 'AAAAA-000-XXXXXXXX';
    $result = Initiator::generateStringFromPattern($pattern);

    expect($result)->toMatch('/^[A-Za-z0-9]{5}-[0-9]{3}-[A-Za-z0-9]{8}$/');
});

it('generates a valid string for expected pattern', function () {
    $pattern = '000000000-000000000';
    $result = Initiator::generateStringFromPattern($pattern);

    // Validate the format using regex
    expect($result)->toMatch('/^[0-9]{9}-[0-9]{9}$/');
});