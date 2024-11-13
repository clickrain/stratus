# Release Notes for Stratus Online Reviews

## 1.1.2 - 2024-11-13

### Fixed

- Fixed an issue where import tasks were incorrectly deleting reviews outside the scope of the request

## 1.1.1 - 2024-11-05

### Fixed

- fixed issue with reviews not being filtered correctly by rating

## 1.1.0 - 2024-10-23

### Breaking Changes

- GraphQL schema has been updated to use an enum for review platform arguments. This change will require updating any queries that filter by platform to use the new enum values. Enum values are a one-to-one match with the string values that were previously used (e.g., `"facebook"` is now `facebook`).

### Added

- Craft 5 support
- Support for filtering GraphQL queries by multiple platforms simultaneously by passing an array of platform values (e.g., `platform: [facebook, google]`)

### Changed

- Modified how review text is stored to accommodate Craft 5 content field related changes that cuased "content" accessors to break

## 1.0.4 - 2024-10-23

### Changed

- Preparation for Craft 5 version

## 1.0.3 - 2023-08-07

### Added

- Added support for filtering eager loaded reviews by content and author

### Fixed

- Fixed CHANGELOG.md formatting

## 1.0.2 - 2023-07-26

### Added

- Added details slideout to listing and review list views

### Fixed

- Fixed graphql issue

## 1.0.1 - 2023-07-19

### Added

- Added ConditionRule support to reviews enabling filtering by listing in the reviews list view
- Added sorting by listing to reviews

### Changed

- Altered default table attributes in list views to not default to showing Stratus UUIDs


## 1.0.0 - 2023-07-19

Initial release