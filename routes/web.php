<?php

use Dedoc\Scramble\Scramble;

Scramble::registerUiRoute(path: 'docs/', api: 'v1');
Scramble::registerJsonSpecificationRoute(path: 'docs/v1.json', api: 'v1');
