# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Changed
- Allow Symfony v6 dependencies

## [0.3.0] - 2021-10-24
### Added
- Add support for PHP v8.
### Fixed
- `replication:monitor` CLI command did not initialise Replication when running
  directly, without `--daemonize`.
### Changed
- Use `ext-memcached` in place of `ext-memcache`.
- Move `Replication::createFromConfig()` static method to an instance method on
  new class `ReplicationFactory`.
- DB config updated to avoid offensive term. Servers are set under `replicas`.
- Reduce visibility of internal methods and properties.
### Removed
- Removed `replication:stats` CLI command which was not functional.
- Removed support for PHP versions <= v7.3 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

## [0.2.0] - 2021-09-22
- Change namespace to `Phlib\DbHelperReplication` to avoid conflicts with
  `phlib/db-helper` package.

## [0.1.0] - 2021-09-22
- Add *Replication* helper from `phlib/db`.
