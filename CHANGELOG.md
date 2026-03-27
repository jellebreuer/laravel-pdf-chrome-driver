# Changelog

All notable changes to `laravel-make-pdf` will be documented in this file.

## v0.3.0 - 2026-03-27

### What's Changed

#### Breaking

* Dropped Laravel 10 support
* Dropped PHP 8.1 support (minimum is now PHP 8.2)

#### Added

* Laravel 13 support
* Cleanup command (make-pdf:cleanup) for killing orphaned chromedriver and chrome-headless-shell processes
* Dedicated chromedriver process management for better teardown

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.2.1...v1.0.0

## v0.2.1 - 2025-12-19

### What's Changed

* Added detection of linux on ARM and added config options + warnings by @jellebreuer in https://github.com/jellebreuer/laravel-make-pdf/pull/42

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.2.0...v0.2.1

## v0.2.0 - 2025-11-27

### What's Changed

Changed package name to breuer/laravel-make-pdf

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.1.0...v0.2.0

## v0.1.0 - 2025-11-27

### What's Changed

Changed username from jbreuer95 to jellebreuer

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.10...v0.1.0

## v0.0.10 - 2025-11-27

### What's Changed

- Use a temporary file for HTML in PDF generation

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.9...v0.0.10

## v0.0.9 - 2025-06-25

### What's Changed

- find an available port to allow multiple instances by @jellebreuer in https://github.com/jellebreuer/laravel-make-pdf/pull/27

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.8...v0.0.9

## v0.0.8 - 2025-05-13

### What's Changed

- Laravel 12 support by @sofyan-1999 in https://github.com/jellebreuer/laravel-make-pdf/pull/21

### New Contributors

- @sofyan-1999 made their first contribution in https://github.com/jellebreuer/laravel-make-pdf/pull/21

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.7...v0.0.8

## v0.0.7 - 2024-11-11

### What's Changed

- Bump larastan/larastan from 2.9.9 to 2.9.10 by @dependabot in https://github.com/jellebreuer/laravel-make-pdf/pull/4
- add save and raw methods

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.6...v0.0.7

## v0.0.6 - 2024-10-23

### What's Changed

- added support for margins, formats and orientation
- fix chrome instance dangling + add install command not run error
- Bump orchestra/testbench from 9.5.0 to 9.5.2 by @dependabot in https://github.com/jellebreuer/laravel-make-pdf/pull/1
- Bump larastan/larastan from 2.9.8 to 2.9.9 by @dependabot in https://github.com/jellebreuer/laravel-make-pdf/pull/2
- Bump nunomaduro/collision from 8.4.0 to 8.5.0 by @dependabot in https://github.com/jellebreuer/laravel-make-pdf/pull/3

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.5...v0.0.6

## v0.0.5 - 2024-10-04

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.4...v0.0.5

## v0.0.4 - 2024-10-03

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.3...v0.0.4

## v0.0.3 - 2024-10-03

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.2...v0.0.3

## v0.0.2 - 2024-10-03

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.0.1...v0.0.2

## v0.0.1 - 2024-10-03

Initial release for testing internally

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/commits/v0.0.1
