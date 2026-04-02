# Changelog

All notable changes to `laravel-make-pdf` will be documented in this file.

## v1.0.0 - 2026-04-02

### What's Changed

#### Architecture overhaul: WebDriver → Chrome DevTools Protocol

This release replaces the Selenium/WebDriver approach with direct communication to `chrome-headless-shell` over the **Chrome DevTools Protocol (CDP) via pipes**. This eliminates the need for ChromeDriver, WebSocket connections, port allocation, and temporary HTML files.

**How it works now:**

- Launches `chrome-headless-shell` with `--remote-debugging-pipe`
- Communicates over fd 3 (write) and fd 4 (read) using null-byte delimited JSON
- Uses `react/child-process` for process management and `react/async` for non-blocking I/O
- HTML is injected via `Page.setDocumentContent` — no temp files written to disk

**Key improvements:**

- Faster PDF generation — no ChromeDriver polling, no port scanning, no HTTP overhead
- No port conflicts — pipes instead of `--remote-debugging-port`
- Safer process cleanup — SIGTERM → SIGKILL with configurable timeout
- Automatic cleanup of stale user-data-dir directories from crashed processes
- Chrome flags aligned with [Gotenberg](https://github.com/gotenberg/gotenberg) for better stability
- Configurable timeout (`make-pdf.timeout`)

#### Breaking Changes

- **Dropped Windows support** — CDP pipes require a Unix-based OS. `ext-pcntl` is now a required extension, which prevents installation on Windows.
- **Removed `php-webdriver/webdriver` dependency** — replaced by `react/child-process` and `react/async`
- **Removed `make-pdf:cleanup` command** — orphaned Chrome processes are no longer possible thanks to the SIGTERM → SIGKILL shutdown sequence with timeout enforcement
- **Removed `chromedriver_path` config option** — ChromeDriver is no longer used
- **Removed `Client::onWindows32()` and `Client::onWindows64()`** — replaced by `Client::onWindows()`
- **Removed `Client::chromeDriverBinary()`**, `Client::getFreePort()`, `Client::isPortFree()`

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.3.1...v1.0.0-alpha

## v0.3.1 - 2026-03-30

### What's Changed

* made cleanup more safe to prevent unintentional closing of other processes

**Full Changelog**: https://github.com/jellebreuer/laravel-make-pdf/compare/v0.3.0...v0.3.1

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
