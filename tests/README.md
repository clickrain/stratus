# Tests

The point of these tests is narrow: catch the moment a new Craft release breaks
an assumption this plugin makes, before a site does.

There are two suites because they answer different questions and have very
different setup costs.

## conformance

No database, no Craft application, runs in well under a second.

It checks that every method the plugin declares still overrides something. That
sounds trivial, but it is the one failure mode nothing else catches: when Craft
drops a hook, the override becomes an ordinary method that nothing calls. PHP
allows it and PHPStan allows it at every level, so the plugin keeps installing
and running while the behaviour quietly stops happening. Craft 5 removed
`hasContent()`, `getContentColumnType()` and `gqlTypeNameByContext()` this way.

```
composer test:conformance
```

Adding a method that overrides nothing fails the suite until you list it in
`CraftOverrideTest::PLUGIN_OWNED`. That is deliberate: it makes "this is the
plugin's own method" a decision someone made rather than a default.

## integration

Boots a real Craft application against a real database, installs the plugin and
exercises it the way Craft does: migrations, element saves and deletes, the
custom element queries, the GraphQL schema, Twig compilation and the webhook
signature check.

```
composer test:integration
```

That is the whole setup. The script starts the database in `docker-compose.yml`
and waits for it to accept connections before running anything, so there is
nothing to install and no `tests/.env` to write.

**The suite drops and recreates every table on each run.** The compose service
publishes on port **33061**, not 3306, precisely so it cannot be pointed at the
MySQL you are running for real Craft sites. It stores its data on tmpfs, so
stopping the container throws the database away and leaves no volume behind.

```
composer db:up      # start it and wait for it to be healthy
composer db:down    # stop and remove it
```

Set `STRATUS_TEST_DB_PORT` if 33061 is taken, and `tests/.env` (copy
`tests/.env.example`) if you would rather use a database of your own.

## Both

```
composer test
```

## In CI

`.github/workflows/ci.yml` runs PHPStan, both suites across PHP 8.2 to 8.4, and
a Craft 6 alpha canary that is allowed to fail. It runs on every push and pull
request, and nightly, because the dependency is what moves, not the code.

There is deliberately no `--prefer-lowest` job. Composer's security advisory
policy refuses to install any Craft 5 below 5.10.10, so such a job could only
ever test a two-patch window that the integration job already covers. The
`^5.0.0` floor in composer.json is a constraint, not a tested claim.

CI runs `composer test:integration` against the same `docker-compose.yml` rather
than a GitHub Actions `services:` block, so a database version that passes
locally is the one CI uses.

There is no committed `composer.lock`, so every run resolves the newest Craft
the constraint allows. That is the whole point of the nightly.
