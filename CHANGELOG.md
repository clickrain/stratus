# Release Notes for Stratus Online Reviews

## 1.1.5 - 2026-09-11

### Fixed

- Fixed the "Login with Stratus" button opening a 404. The settings template read the `baseUrl` property directly rather than calling `getBaseUrl()`, so the popup URL was built from an empty string and resolved against the current site instead of Stratus.
- Fixed webhook signature verification ignoring environment variables. The signing secret is now read through `getWebhookSecret()`, so values such as `$STRATUS_WEBHOOK_SECRET` are resolved before the signature is compared.
- Fixed webhook requests reporting a signature mismatch when no signing secret was configured. The check for a missing secret ran after the comparison it was meant to guard, making it unreachable.
- Webhook signatures are now compared with `hash_equals()` so the comparison runs in constant time.
- Fixed imports failing with a duplicate key error on `stratusUuid` when a record's element could no longer be resolved. Records are now matched by `stratusUuid` against the table itself rather than through the element query, which could not see rows the unique index still enforced. Rows whose element is missing or belongs to another element type are removed so the record is re-imported cleanly.
- Fixed deprecation notices on PHP 8.4 from four parameters that were implicitly nullable through a `null` default. The nullable types are now declared.

### Changed

- Import task failures are now logged as errors, with the exception class and a stack trace. They were previously recorded as a single-line warning, which made a failed import difficult to distinguish from a successful one.
- Added indexes for the columns reviews and listings are filtered and sorted by: `platformPublishedDate` on its own, `platform`, `rating` and `reviewableType` each paired with it, and `name` on listings. Reviews are ordered by `platformPublishedDate` on every query and the control panel sources filter on top of that ordering, none of which had an index available, so those queries were left to a full table scan and a filesort.
- The plugin's schema version has been raised to 1.1.1. It had not changed since 1.0.0 even though migrations were added after that release, which meant Craft would not check for them on update — so some installs may be prompted to run migrations they had not picked up.
- Removed the `craftcms/rector` dev dependency, which was added for the Craft 4 to 5 upgrade and is no longer used, and the plugin's `composer.lock`, which Composer does not read when the plugin is installed as a dependency. Neither affects sites running the plugin.
- The PHP requirement is now `^8.2`, which is what Craft 5 already requires. The previous `^8.0.2` was never installable alongside Craft 5, so nothing that worked before stops working.

### Security

- The API key and webhook secret are now written to your `.env` file when settings are saved, and referenced from the plugin settings as `$STRATUS_API_KEY` and `$STRATUS_WEBHOOK_SECRET`. They were previously stored in project config as literal values. Project config may be version controlled, so storing there could expose sensitive credentials.
- The settings screen now warns whenever either credential is held as a literal value, whatever its source, so a credential synced in from another environment is also flagged.
- If the `.env` file cannot be written the settings are still saved and the credentials are left as they were, with a message naming the ones that remain in plain text.

To pick this up on an existing install, re-save the Stratus settings and the credentials already in project config will be moved into `.env`. Rotate any credentials that were previously committed if they exist in a repository's history.

## 1.1.4 - 2025-05-29

### Fixed

- Fixed the Stratus base URL being empty when the `STRATUS_BASE_URL` environment variable was not set. The fallback to `https://app.gostratus.io` is now applied by the settings getter, so the base URL resolves whether or not the variable is defined.

## 1.1.3 - 2025-05-13

### Fixed

- Fixed stored review data being deleted when plugin settings were saved. Saving settings queued a full refresh that removed existing reviews before re-importing them, which could leave a site without review data — most noticeably when moving from the standalone plugin to the Plugin Store release.

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