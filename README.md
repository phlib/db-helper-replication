# phlib/db-helper-replication

[![Code Checks](https://img.shields.io/github/actions/workflow/status/phlib/db-helper-replication/code-checks.yml?logo=github)](https://github.com/phlib/db-helper-replication/actions/workflows/code-checks.yml)
[![Codecov](https://img.shields.io/codecov/c/github/phlib/db-helper-replication.svg?logo=codecov)](https://codecov.io/gh/phlib/db-helper-replication)
[![Latest Stable Version](https://img.shields.io/packagist/v/phlib/db-helper-replication.svg?logo=packagist)](https://packagist.org/packages/phlib/db-helper-replication)
[![Total Downloads](https://img.shields.io/packagist/dt/phlib/db-helper-replication.svg?logo=packagist)](https://packagist.org/packages/phlib/db-helper-replication)
![Licence](https://img.shields.io/github/license/phlib/db-helper-replication.svg)

DB helpers to complement phlib/db

This package, *db-helper-replication*, is split out from *phlib/db-helper*
due to the additional process-control dependencies which users of the more
typical helpers may not require or have available.

## Installation

```php
composer require phlib/db-helper-replication
```

## Usage

### Replication

The Replication helper monitors replica lag, which it stores in Memcache. This
known lag can then be used to throttle long-running processes by introducing
variable amounts of sleep.

Set up replica monitoring using the CLI script
(you might consider using Monit to run this automatically):

```sh
./vendor/bin/db replication:monitor -c path/to/config.php -p /var/run/db-replication.pid -d start
```

```php
$config = require 'path/to/config.php';
$replication = Replication::createFromConfig($config);

while ([...]) {
    [... some repetitive iteration, like writing thousands of records ...]
    
    $replication->throttle();
}
```

Your config file might look something like this:

```php
<?php
$config = [
    // primary
    'host'     => '10.0.0.1',
    'username' => 'foo',
    'password' => 'bar',
    'replicas' => [
        [
            'host'     => '10.0.0.2',
            'username' => 'foo',
            'password' => 'bar',
        ],
    ],
    'storage' => [
        'class' => \Phlib\DbHelperReplication\Replication\Memcache::class,
        'args'  => [[]],
    ],
];

return $config;
```

## License

This package is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
