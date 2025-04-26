<?php

use BadgeGenerator\BadgeGenerator;

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time
| to create even more expectations.
|
*/

expect()->extend('toBeBadge', function () {
    return $this->toBeInstanceOf(BadgeGenerator::class);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some leftover code which you don't
| want to keep in your files. Here you can also expose helpers as global functions to help
| you to keep your files as clean as possible.
|
*/

function createBadgeGenerator(array $inputs = []): BadgeGenerator
{
    return new BadgeGenerator($inputs);
}
