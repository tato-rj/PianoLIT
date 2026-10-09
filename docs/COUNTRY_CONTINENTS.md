# Fill country continents

Deploy the code, then run:

```sh
php artisan migrate
php artisan countries:fill-continents --dry-run
php artisan countries:fill-continents
```

The migration adds a nullable string `countries.continent`. The one-time command
fills every existing country, replacing incorrect continent values as well as
filling null/empty values. Rerunning it is safe: already-correct rows are not
written. Only `continent` changes; names, nationalities, flag codes, timestamps,
and relationships remain intact. New countries still default to null until the
command runs again or a continent is explicitly assigned.

The command uses `flag_code` as an ISO alpha-2 code, ignoring surrounding spaces
and case, then falls back to normalized English country names. Explicit aliases
cover common alternate names and UK constituent-country flags. It does not use
fuzzy matches. An unrecognized country aborts the entire update and prints its
ID, name, and flag code so it can be corrected before retrying. All writes are
in one transaction; on MySQL, existing country rows are locked during the update.
Avoid adding countries while running this one-time backfill.

The dry run lists current and proposed continents without changing any records.
The command needs no network connection, API credentials, or new dependencies.
`Country` hides `continent` from default array/JSON serialization to preserve
existing mobile response contracts; PHP code can read `$country->continent`.

## Dataset and attribution

`resources/data/country-continents.json` is derived from
[GeoNames countryInfo.txt](https://download.geonames.org/export/dump/countryInfo.txt),
downloaded on 2026-10-09. GeoNames provides the data under
[Creative Commons Attribution 4.0](https://creativecommons.org/licenses/by/4.0/);
see [GeoNames](https://www.geonames.org/) and its
[data export information](https://www.geonames.org/export/).
This adaptation retains only ISO alpha-2 code, English country name, and
continent, expanding the source continent codes to full English names.

The seven labels are Africa, Antarctica, Asia, Europe, North America, Oceania,
and South America. Each country has one continent, following GeoNames even for
transcontinental countries: for example, Russia is Europe and Turkey is Asia.
Territories follow their own source classification. The snapshot also includes
GeoNames' legacy Serbia and Montenegro and Netherlands Antilles entries for
older catalogue records. To refresh, download that source and regenerate these
three fields, then review the mapping diff and rerun the isolated command tests.
