# Changelog

All notable changes to `dropbox-api` will be documented in this file

## 1.23.0 - 2025-01-06

### What's Changed

* Run tests using Pest by @jmsche in https://github.com/spatie/dropbox-api/pull/124
* Bump dependabot/fetch-metadata from 1.5.1 to 1.6.0 by @dependabot in https://github.com/spatie/dropbox-api/pull/125
* Bump actions/checkout from 2 to 4 by @dependabot in https://github.com/spatie/dropbox-api/pull/126
* Bump aglipanci/laravel-pint-action from 2.3.0 to 2.3.1 by @dependabot in https://github.com/spatie/dropbox-api/pull/129
* Bump stefanzweifel/git-auto-commit-action from 4 to 5 by @dependabot in https://github.com/spatie/dropbox-api/pull/128
* Bump ramsey/composer-install from 2 to 3 by @dependabot in https://github.com/spatie/dropbox-api/pull/131
* Bump aglipanci/laravel-pint-action from 2.3.1 to 2.4 by @dependabot in https://github.com/spatie/dropbox-api/pull/133
* Bump dependabot/fetch-metadata from 1.6.0 to 2.2.0 by @dependabot in https://github.com/spatie/dropbox-api/pull/136
* PHP 8.4 deprecations by @simoheinonen in https://github.com/spatie/dropbox-api/pull/138

### New Contributors

* @simoheinonen made their first contribution in https://github.com/spatie/dropbox-api/pull/138

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.22.0...1.23.0

## 1.22.0 - 2023-06-08

### What's Changed

- Cleanup and improvements by @jmsche in https://github.com/spatie/dropbox-api/pull/123
- Drop support for old PHP versions

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.21.2...1.22.0

## 1.21.2 - 2023-06-07

### What's Changed

- Bump dependabot/fetch-metadata from 1.3.6 to 1.4.0 by @dependabot in https://github.com/spatie/dropbox-api/pull/115
- Bump dependabot/fetch-metadata from 1.4.0 to 1.5.0 by @dependabot in https://github.com/spatie/dropbox-api/pull/117
- Bump dependabot/fetch-metadata from 1.5.0 to 1.5.1 by @dependabot in https://github.com/spatie/dropbox-api/pull/118
- Fix README badges by @jmsche in https://github.com/spatie/dropbox-api/pull/120
- Allow graham-campbell/guzzle-factory v7 by @jmsche in https://github.com/spatie/dropbox-api/pull/119
- Fix PHPUnit errors & failures by @jmsche in https://github.com/spatie/dropbox-api/pull/121

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.21.1...1.21.2

## 1.21.1 - 2023-03-17

### What's Changed

- Add PHP 8.2 support by @patinthehat in https://github.com/spatie/dropbox-api/pull/110
- Add dependabot automation by @patinthehat in https://github.com/spatie/dropbox-api/pull/109
- Update Dependabot Automation by @patinthehat in https://github.com/spatie/dropbox-api/pull/112
- Bump dependabot/fetch-metadata from 1.3.5 to 1.3.6 by @dependabot in https://github.com/spatie/dropbox-api/pull/113
- Allow graham-campbell/guzzle-factory v6 by @jmsche in https://github.com/spatie/dropbox-api/pull/114

### New Contributors

- @patinthehat made their first contribution in https://github.com/spatie/dropbox-api/pull/110
- @dependabot made their first contribution in https://github.com/spatie/dropbox-api/pull/113

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.21.0...1.21.1

## 1.21.0 - 2022-09-27

### What's Changed

- Add ability to set the namespace ID for requests by @rstefanic in https://github.com/spatie/dropbox-api/pull/105

### New Contributors

- @rstefanic made their first contribution in https://github.com/spatie/dropbox-api/pull/105

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.20.2...1.21.0

## 1.20.2 - 2022-06-24

### What's Changed

- uploadSessionStart and uploadSessionFinish can accept resource by @dmitryuk in https://github.com/spatie/dropbox-api/pull/102

### New Contributors

- @dmitryuk made their first contribution in https://github.com/spatie/dropbox-api/pull/102

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.20.1...1.20.2

## 1.20.1 - 2022-03-29

## What's Changed

- Fix refreshable token response by @einarsozols in https://github.com/spatie/dropbox-api/pull/100

## New Contributors

- @einarsozols made their first contribution in https://github.com/spatie/dropbox-api/pull/100

**Full Changelog**: https://github.com/spatie/dropbox-api/compare/1.20.0...1.20.1

## Unreleased

- Added refreshable token provider interface.

## 1.19.1 - 2021-07-04

- fix compability with guzzlehttp/psr7 2.0 (#91)

## 1.19.0 - 2021-06-18

- add autoRename parameter for move() method (#89)

## 1.18.0 - 2021-05-27

- add autorename option to upload method (#86)

## 1.17.1 - 2021-03-01

- allow graham-campbell/guzzle-factory v5 (#79)

## 1.17.0 - 2020-12-08

- `TokenProvider` interface for accesstokens (#76)

## 1.16.1 - 2020-11-27

- allow PHP 8

## 1.16.0 - 2020-09-25

- allow the Client to work with Dropbox business accounts

## 1.15.0 - 2020-07-09

- allow Guzzle 7 (#70)

## 1.14.0 - 2020-05-11

- add support for app authentication and no authentication

## 1.13.0 - 2020-05-03

- added `downloadZip` (#66)

## 1.12.0 - 2020-02-04

- add `search` method

## 1.11.1 - 2019-12-12

- make compatible with PHP 7.4

## 1.11.0 - 2019-07-04

- add `$response` to `BadRequest`

## 1.10.0 - 2019-07-01

- move retry stuff to package

## 1.9.0 - 2019-05-21

- make guzzle retry 5xx and 429 responses

## 1.8.0 - 2019-04-13

- add `getEndpointUrl`
- drop support for PHP 7.0

## 1.7.1 - 2019-02-13

- fix for `createSharedLinkWithSettings` with empty settings

## 1.7.0 - 2019-02-06

- add getter and setter for the access token

## 1.6.6 - 2018-07-19

- fix for piped streams

## 1.6.5 - 2018-01-15

- adjust `normalizePath` to allow id/rev/ns to be queried

## 1.6.4 - 2017-12-05

- fix max chunk size

## 1.6.1 - 2017-07-28

- fix for finishing upload session

## 1.6.0 - 2017-07-28

- add various new methods to enable chuncked uploads

## 1.5.3 - 2017-07-28

- use recommended `move_v2` method to move files

## 1.5.2 - 2017-07-17

- add missing parameters to `listSharedLinks` method

## 1.5.1 - 2017-07-17

- fix broken `revokeToken` and `getAccountInfo`

## 1.5.0 - 2017-07-11

- add `revokeToken` and `getAccountInfo`

## 1.4.0 - 2017-07-11

- add `listSharedLinks`

## 1.3.0 - 2017-07-04

- add error code to thrown exception

## 1.2.0 - 2017-04-29

- added `createSharedLinkWithSettings`

## 1.1.0 - 2017-04-22

- added `listFolderContinue`

## 1.0.1 - 2017-04-19

- Bugfix: set default value for request body

## 1.0.0 - 2017-04-19

- initial release
