# Changelog

All notable changes to `forestry/logcabin-laravel` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-17

Initial public release.

### Added

- Queued log delivery to a central Log Cabin panel via a plain bearer-token HTTP client.
- Automatic error capture by appending a `logcabin` log channel to the `stack` channel
  (opt out with `LOGCABIN_AUTO_ATTACH=false`).
- Manual reporting through the `LogCabin` facade (`report()` and `reportException()`).
- Self-scheduled `logcabin:heartbeat` command reporting PHP/Laravel versions, queue depth,
  disk usage and scheduler liveness.
- Publishable configuration file (`--tag=logcabin-config`).

[1.0.0]: https://github.com/forestrylabs/logcabin-laravel/releases/tag/v1.0.0
