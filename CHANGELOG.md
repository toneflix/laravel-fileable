# Changelog

All notable changes to `laravel-fileable` will be documented in this file

## 2.1.4 - 2025-01-19

* Pass the exact media_file_info to the fileInfo argument of the FileSaved event.

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.1.3...2.1.4

## 2.1.3 - 2025-01-19

* Add the original filename from the upload request to the FileSaved event payload.

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.1.2...2.1.3

## 2.1.2 - 2025-01-19

* Emit the FileSaved event after saving a file

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.1.1...2.1.2

## 2.1.1 - 2025-01-19

* feat: Add config option and implementation to allow file name customization when saving.

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.0.9...2.1.1

## 2.1.0 - 2025-01-11

- Register the fileable in the retrieved, creating, updating and saving events to allow for model based configuration.

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.0.9...2.1.0

## v2.0.8 - 2024-09-13

- [Provide type info for mediaInfo Media Facade method](https://github.com/toneflix/laravel-fileable/commit/73dfe5f6b478d79b2b04215f7d54b698ec979b54)
- [Allow saving files directly by passing UploadedFile instance as $file_name param to save method of Media class.](https://github.com/toneflix/laravel-fileable/commit/d21c311742723483a6ca402ba386e2f1a99ec011)

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.0.7...2.0.8

## 2.0.6 - 2024-07-29

### What's Changed

* Dev by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/10

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.0.5...2.0.6

## 2.0.5 - 2024-07-25

### What's Changed

* Dev by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/1
* Dev by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/2
* Add built in secure and dynamic link support by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/3
* Dev by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/4
* Dev by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/5
* Update README.md by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/6
* Update composer.json by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/7
* Bump dependabot/fetch-metadata from 2.1.0 to 2.2.0 by @dependabot in https://github.com/toneflix/laravel-fileable/pull/8
* Dev by @3m1n3nc3 in https://github.com/toneflix/laravel-fileable/pull/9

### New Contributors

* @3m1n3nc3 made their first contribution in https://github.com/toneflix/laravel-fileable/pull/1
* @dependabot made their first contribution in https://github.com/toneflix/laravel-fileable/pull/8

**Full Changelog**: https://github.com/toneflix/laravel-fileable/compare/2.0.4...2.0.5

## 1.0.0 - 201X-XX-XX

- initial release
