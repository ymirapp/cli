# Changelog

## [2.0.1](https://github.com/ymirapp/cli/compare/v2.0.0...v2.0.1) (2026-01-22)


### Bug Fixes

* Display region in project selection choices ([f41f87a](https://github.com/ymirapp/cli/commit/f41f87a7859bbe6b64480fe8c663e1cb7e916d68))
* Prevent resource name collisions in `ApiClient` collections ([50d6214](https://github.com/ymirapp/cli/commit/50d621420991d90401c64c62f6cbe39959534ebb))

## [2.0.0](https://github.com/ymirapp/cli/compare/v1.54.1...v2.0.0) (2026-01-20)


### ⚠ BREAKING CHANGES

* rewrite cli to support non-wordpress projects

### Features

* Rewrite cli to support non-wordpress projects ([33305ac](https://github.com/ymirapp/cli/commit/33305ac4d201add879e62f8e574f168c1aeba99e))


### Bug Fixes

* Validate `--platform` option in `Dockerfile` ([b11eb82](https://github.com/ymirapp/cli/commit/b11eb826403270f1e5b6040200af6b173ad1590c))

## [1.54.1](https://github.com/ymirapp/cli/compare/v1.54.0...v1.54.1) (2025-10-03)


### Bug Fixes

* Abort the project team guard if there's no active team ([3f9dc18](https://github.com/ymirapp/cli/commit/3f9dc181ac9da41a8a27c2d9053cb852cc58aa86))

## [1.54.0](https://github.com/ymirapp/cli/compare/v1.53.0...v1.54.0) (2025-10-03)


### Features

* Add support for building the image with a different architecture than the build environment ([9e9cd35](https://github.com/ymirapp/cli/commit/9e9cd3509a45998bb5b24ec8fd5b4eb5112ce316))
* Support both `deployment` and `deployment.type` configuration options ([653ee67](https://github.com/ymirapp/cli/commit/653ee6738627824a6b000abb439dde7c549ec719))


### Bug Fixes

* Abort command when active team conflicts with project team ([2f59cc8](https://github.com/ymirapp/cli/commit/2f59cc867dadfb036facb1be72e51e84ecf28e14))
* Add `execute` to list of formatted names ([53289dc](https://github.com/ymirapp/cli/commit/53289dc74816a060b8a63e5fcb096b3a4bf23705))

## [1.53.0](https://github.com/ymirapp/cli/compare/v1.52.0...v1.53.0) (2025-08-30)


### Features

* Update location of deployment type in project configuration ([76c35cf](https://github.com/ymirapp/cli/commit/76c35cf54544b77be0694641b3f9937c1fc65f06))

## [1.52.0](https://github.com/ymirapp/cli/compare/v1.51.1...v1.52.0) (2025-07-05)


### Features

* Bump deployed wp-cli version to 2.12.0 ([5cf989a](https://github.com/ymirapp/cli/commit/5cf989aa73f0821dc259b4e4844f95acc51336c5))


### Bug Fixes

* All directory path methods should return a relative path if there's no base path ([f4e28d8](https://github.com/ymirapp/cli/commit/f4e28d88adeee6384a684c607fc3e60756ab7cf6))
* Only use relative path to uploads directory when no `path` is given ([25ca259](https://github.com/ymirapp/cli/commit/25ca2599e3bcc041c7d97a8e5e6ab1b45246cd22))

## [1.51.1](https://github.com/ymirapp/cli/compare/v1.51.0...v1.51.1) (2025-04-03)


### Bug Fixes

* `askSlug` method should return an empty string if the user answers nothing ([04fae1e](https://github.com/ymirapp/cli/commit/04fae1e713473be772d080b316ee6bd365ffb656))
* `cache:modify` command also needs new formatted cache type descriptions ([2506976](https://github.com/ymirapp/cli/commit/2506976a578628682a56ce77044bb44dd4ceaee0))

## [1.51.0](https://github.com/ymirapp/cli/compare/v1.50.2...v1.51.0) (2025-03-30)


### Features

* Add cache engine to `cache:list` command ([af4e42f](https://github.com/ymirapp/cli/commit/af4e42fbfa469f924a7305cb1a39c1ad2b488ee8))
* Add support for creating valkey cache clusters ([f630414](https://github.com/ymirapp/cli/commit/f63041467b870a4af1a290bbd2ddbd33991f0f5f))
* Add support for radicle ([608eb70](https://github.com/ymirapp/cli/commit/608eb7036da8ea9f212b53414336bd48c41bc2be))


### Bug Fixes

* Fix false negative when detecting wordpress installation ([9786217](https://github.com/ymirapp/cli/commit/978621748f31adb234b16a35ceebd51dd6cf7735))

## [1.50.2](https://github.com/ymirapp/cli/compare/v1.50.1...v1.50.2) (2025-01-12)


### Bug Fixes

* Check if we have a string before passing it to `trim` ([601ee0e](https://github.com/ymirapp/cli/commit/601ee0eca90745cb7da7378b85196ad7bd4f610c))
* Display docker warning if it's not installed ([331b288](https://github.com/ymirapp/cli/commit/331b28801d11ada5a2e7cc696e1aaddd5a6e3438))
* Don't put a timeout when running `docker push` command ([95f4213](https://github.com/ymirapp/cli/commit/95f4213b785e4999722889b8f215342ef4115b4a))

## [1.50.1](https://github.com/ymirapp/cli/compare/v1.50.0...v1.50.1) (2024-12-27)


### Bug Fixes

* Don't put a timeout when running `docker build` command ([b5d4f4d](https://github.com/ymirapp/cli/commit/b5d4f4d2d4c1cecbe386866b244183a737f6fddb))

## [1.50.0](https://github.com/ymirapp/cli/compare/v1.49.0...v1.50.0) (2024-12-26)


### Features

* Add support for podman to build container images ([afee2ed](https://github.com/ymirapp/cli/commit/afee2ed7c99687da916dedd307467febf8c2694c)), closes [#53](https://github.com/ymirapp/cli/issues/53)
* Make container image deployment the default ([77b2ec3](https://github.com/ymirapp/cli/commit/77b2ec3f8a8f3c3aa4653038d7f1a7e01b0641a1))

## [1.49.0](https://github.com/ymirapp/cli/compare/v1.48.1...v1.49.0) (2024-12-20)


### Features

* Switch to using php for database import/export ([dd6bb08](https://github.com/ymirapp/cli/commit/dd6bb089287d8b36f90ea936976719f1118dd779))

## [1.48.1](https://github.com/ymirapp/cli/compare/v1.48.0...v1.48.1) (2024-11-20)


### Bug Fixes

* Use dynamic wait for establishing ssh tunnel to database server ([6159adc](https://github.com/ymirapp/cli/commit/6159adcb7e6486c5f385449f7ea392e9be5500e0))

## [1.48.0](https://github.com/ymirapp/cli/compare/v1.47.1...v1.48.0) (2024-09-28)


### Features

* bump deployed wp-cli version to 2.11.0 ([22f02eb](https://github.com/ymirapp/cli/commit/22f02eb79b995db66abee60cd5f7027677797114))

## [1.47.1](https://github.com/ymirapp/cli/compare/v1.47.0...v1.47.1) (2024-09-23)


### Bug Fixes

* need to set the access token in the api client once we log in ([36304c4](https://github.com/ymirapp/cli/commit/36304c4d689f7cb47a0991a26dbfd98989db336d))

## [1.47.0](https://github.com/ymirapp/cli/compare/v1.46.0...v1.47.0) (2024-08-30)


### Features

* add support for configuring projects using the cloudflare plugin ([5051c1d](https://github.com/ymirapp/cli/commit/5051c1d7caf3850d2a98c4e6867eab68b5012b05))
* default image deployment question to `true` if docker is installed ([44cafd2](https://github.com/ymirapp/cli/commit/44cafd201d5d218023f2f9ed8cdcd1bea7ce778a))


### Bug Fixes

* wasn't passing the password properly to ftp adapter ([ac315ac](https://github.com/ymirapp/cli/commit/ac315acc2683ecfafdd18929c4c2f5f2cc66d7e6))

## [1.46.0](https://github.com/ymirapp/cli/compare/v1.45.0...v1.46.0) (2024-06-28)


### Features

* add `--skip-ssl` option for `database:import` command ([07d4c80](https://github.com/ymirapp/cli/commit/07d4c8004357d2fa90c746ee427800a1dfb8b210))
* let `uploads:import` continue when a file path is corrupted ([d87a703](https://github.com/ymirapp/cli/commit/d87a7038baca94c84b702ba6609867db9603de65))

## [1.45.0](https://github.com/ymirapp/cli/compare/v1.44.0...v1.45.0) (2024-02-08)


### Features

* bump deployed wp-cli version to 2.10.0 ([064aef8](https://github.com/ymirapp/cli/commit/064aef87e3cc126a1118204bc4efb38614df3227))
* display warnings when validating project configuration ([d25377d](https://github.com/ymirapp/cli/commit/d25377d86fc302e71410a77691ae85e13a020ee2))

## [1.44.0](https://github.com/ymirapp/cli/compare/v1.43.2...v1.44.0) (2024-02-07)


### Features

* add `cache:modify` command ([50dfd44](https://github.com/ymirapp/cli/commit/50dfd4423d884753868ed3a4711778ec1e7b10b9))

## [1.43.2](https://github.com/ymirapp/cli/compare/v1.43.1...v1.43.2) (2024-01-20)


### Bug Fixes

* use `which` instead of `command -v` for windows compatibility ([f01e5a8](https://github.com/ymirapp/cli/commit/f01e5a8ed9de0637490d911536ecefa79f6ccdcf))

## [1.43.1](https://github.com/ymirapp/cli/compare/v1.43.0...v1.43.1) (2023-10-24)


### Bug Fixes

* fix infinite loop with `isInteractive` method call ([8b65d02](https://github.com/ymirapp/cli/commit/8b65d0285d1de1d4563742be60616b363e1eeaeb))

## [1.43.0](https://github.com/ymirapp/cli/compare/v1.42.0...v1.43.0) (2023-10-23)


### Features

* add `force-assets` option to `project:deploy` command ([ca3d806](https://github.com/ymirapp/cli/commit/ca3d8067531aed033415582e31dda8dc4a96f1a9))
* say how many asset files ymir was unable to process ([6787fc2](https://github.com/ymirapp/cli/commit/6787fc2738faca8a9cdcd7aa4aa4ebbfa21a1e7a))
