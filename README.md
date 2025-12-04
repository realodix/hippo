[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/realodix/haiku)

# Realodix Haiku

Haiku is a powerful command-line tool for managing ad-blocker filter lists. It automates repetitive tasks such as merging sources, optimizing, and tidying up filter lists effortlessly. With a built-in caching system, Haiku skips unchanged files since the last run, resulting in significantly faster processing.


## Features

- **Filter List Building:** Builds unified outputs from multiple local or remote filter lists into a single file, regenerating metadata and stripping unnecessary lines such as comments.
- **Rule Sorting:** Sorts filter rules alphabetically for consistent and readable ordering.
- **Rules Combining:** Merges rules with identical patterns that differ only by domain, reducing duplication and improving efficiency.
- **Option Sorting:** Sorts filter options (e.g., `$third-party,script,domain=...`) into a standardized and predictable sequence.
- **Caching:** Caches processed files and re-processes only those that have changed, speeding up subsequent runs.
- **Configuration:** Easily configure builder and fixer behaviors via a simple `haiku.yml` file.

A few examples of transformations applied during optimization:

```adblock
! Before
example.com##+js(aopw, Fingerprint2)
##.top-r-ads
example.com###ads
-banner-$image,domain=example.com
||example.com^$script,domain=a.com,third-party
-banner-$image,domain=example.org
example.com###ads
google.com,example.com## .advert
##.top-banners

! After
-banner-$image,domain=example.com|example.org
||example.com^$third-party,script,domain=a.com
example.com###ads
example.com,google.com##.advert
##.top-banners
##.top-r-ads
example.com##+js(aopw, Fingerprint2)
```


## Installation

Install the package via [Composer](https://getcomposer.org/):

```sh
composer require realodix/haiku
```

Composer will install Haiku executable in its `bin-dir` which defaults to `vendor/bin`.


## Quick Start

1. **Initialize the configuration file for Haiku:**
    ```sh
    vendor/bin/haiku init
    ```

    Run this command to create a `haiku.yml` configuration file.

2. **Build Filters:**

    ```sh
    vendor/bin/haiku build
    ```

    This builds unified outputs from filter sources as defined in your config.

3. **Fix & Optimize:**

    ```sh
    vendor/bin/haiku fix
    ```

    This tidies and optimizes the specified filter file or directory.


## Usage

Haiku provides two main commands: `build` for merging sources and `fix` for optimization.

### Building Filter Lists
Builds multiple source files into a single output file as defined in your `haiku.yml`, including metadata regeneration and stripping unnecessary lines such as comments.

```sh
vendor/bin/haiku build [options]
```

#### Options:
- `--force`: Ignore cache and rebuild all sources.
- `--config`: Use a custom configuration file path. Example: `--config ./config.yml`.

### Fixing Filter Lists
Optimizes existing filter files or directories by cleaning syntax, sorting rules, and combining compatible patterns.

```sh
vendor/bin/haiku fix [options]
```

#### Options:
- `--path`: Path to the filter file or directory to process.
- `--force`: Process all files regardless of cache.
- `--config`: Use a custom configuration file path. Example: `--config ./config.yml`.
- `--cache`: Specify a custom cache path. Example: `--cache ./custom-cache/`.


## Configuration

The configuration file should be a valid YAML file. The following options are available:

```yaml
# cache_dir: .tmp

# Settings for the `fix` command
fixer:
  paths:
    - folder_1/file.txt
    - folder_2
  excludes:
    - excluded_file.txt
    - some/path/to/file.txt
    - path/to/source

# Settings for the `build` command
builder:
  output_dir: dist
  filter_list:
    # First filter list
    - filename: general_blocklist.txt # Required
      remove_duplicates: true
      metadata:
        header: Adblock Plus 2.0
        title: General Blocklist
        version: true
        custom: |
          Description: Filter list that specifically removes adverts.
          Expires: 6 days (update frequency)
          Homepage: https://example.org/
          License: MIT
      source: # Required
        - blocklists/general/local-rules.txt
        - https://cdn.example.org/blocklists/general.txt

    # Second filter list
    - filename: custom_privacy.txt
      date_modified: false
      remove_duplicates: true
      source:
        - sources/tracking_domains-1.txt
        - sources/tracking_domains-2.txt
```

See [configuration reference](./docs/configuration.md) for more details.

> [!NOTE]
> You can delete any configurations you don't need. Haiku will use the default values ​​instead.


## Contributing

Contributions are welcome! Please:

1. Fork the repo and create a feature branch.
2. Add tests for new features.
3. Ensure code passes `composer check`.
4. Submit a PR with a clear description.

Report bugs or suggest features via Issues.


## License

This project is licensed under the [MIT License](./LICENSE).
