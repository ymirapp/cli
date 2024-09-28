# Changelog

## [1.49.0](https://github.com/ymirapp/cli/compare/v1.48.0...v1.49.0) (2024-09-28)


### Features

* add `--skip-ssl` option for `database:import` command ([07d4c80](https://github.com/ymirapp/cli/commit/07d4c8004357d2fa90c746ee427800a1dfb8b210))
* add `cache:modify` command ([50dfd44](https://github.com/ymirapp/cli/commit/50dfd4423d884753868ed3a4711778ec1e7b10b9))
* add `force-assets` option to `project:deploy` command ([ca3d806](https://github.com/ymirapp/cli/commit/ca3d8067531aed033415582e31dda8dc4a96f1a9))
* add support for configuring projects using the cloudflare plugin ([5051c1d](https://github.com/ymirapp/cli/commit/5051c1d7caf3850d2a98c4e6867eab68b5012b05))
* bump deployed wp-cli version to 2.10.0 ([064aef8](https://github.com/ymirapp/cli/commit/064aef87e3cc126a1118204bc4efb38614df3227))
* bump deployed wp-cli version to 2.11.0 ([22f02eb](https://github.com/ymirapp/cli/commit/22f02eb79b995db66abee60cd5f7027677797114))
* default image deployment question to `true` if docker is installed ([44cafd2](https://github.com/ymirapp/cli/commit/44cafd201d5d218023f2f9ed8cdcd1bea7ce778a))
* display warnings when validating project configuration ([d25377d](https://github.com/ymirapp/cli/commit/d25377d86fc302e71410a77691ae85e13a020ee2))
* let `uploads:import` continue when a file path is corrupted ([d87a703](https://github.com/ymirapp/cli/commit/d87a7038baca94c84b702ba6609867db9603de65))


### Bug Fixes

* fix infinite loop with `isInteractive` method call ([8b65d02](https://github.com/ymirapp/cli/commit/8b65d0285d1de1d4563742be60616b363e1eeaeb))
* need to set the access token in the api client once we log in ([36304c4](https://github.com/ymirapp/cli/commit/36304c4d689f7cb47a0991a26dbfd98989db336d))
* use `which` instead of `command -v` for windows compatibility ([f01e5a8](https://github.com/ymirapp/cli/commit/f01e5a8ed9de0637490d911536ecefa79f6ccdcf))
* wasn't passing the password properly to ftp adapter ([ac315ac](https://github.com/ymirapp/cli/commit/ac315acc2683ecfafdd18929c4c2f5f2cc66d7e6))

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
