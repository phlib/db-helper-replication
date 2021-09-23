# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Changed
- Use `ext-memcached` in place of `ext-memcache`.
### Removed
- Removed support for PHP versions < v7.1 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

## [0.2.0] - 2021-09-22
- Change namespace to `Phlib\DbHelperReplication` to avoid conflicts with
  `phlib/db-helper` package.

## [0.1.0] - 2021-09-22
- Add *Replication* helper from `phlib/db`.
