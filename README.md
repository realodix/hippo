# Realodix Hippo

> [!CAUTION]
>
> This project is not finished yet, work in progress.
>

Hippo is a powerful command-line tool for managing ad-blocker filter lists. It automates repetitive tasks such as merging sources, optimizing, and tidying up filter lists effortlessly. With a built-in caching system, Hippo skips unchanged files since the last run, resulting in significantly faster processing.


## Features

- **Filter List Building:** Builds unified outputs from multiple local or remote filter lists into a single file, regenerating metadata and stripping unnecessary lines such as comments.
- **Rule Sorting:** Sorts filter rules alphabetically for consistent and readable ordering.
- **Rules Combining:** Merges rules with identical patterns that differ only by domain, reducing duplication and improving efficiency.
- **Option Sorting:** Sorts filter options (e.g., `$third-party,script,domain=...`) into a standardized and predictable sequence.
- **Caching:** Caches processed files and re-processes only those that have changed, speeding up subsequent runs.
- **Configuration:** Easily configure builder and fixer behaviors via a simple `hippo.yml` file.

Below are a few examples of transformations applied during optimization.

```adblock
! Before
##.top-r-ads
example.com###ads
-banner-$image,domain=example.com
||example.com^$script,domain=a.com,third-party
-banner-$image,domain=example.org
example.com###ads
google.com,example.com## .advert
##.top-banners

! After
example.com###ads
example.com,google.com##.advert
##.top-banners
##.top-r-ads
-banner-$image,domain=example.com|example.org
||example.com^$third-party,script,domain=a.com
```


## Installation

Install the package via Composer:

```sh
composer require realodix/hippo
```

This adds the `hippo` executable to `./vendor/bin/hippo`. Make sure `./vendor/bin` is included in your `$PATH` environment variable, or run the executable directly.


## Quick Start

1. **Create Configuration:** Create a `hippo.yml` configuration file and place it to your project root (see Configuration for details).
2. **Build Filters:**

    ```sh
    ./vendor/bin/hippo build
    ```

    This builds unified outputs from filter sources as defined in your config.

3. **Fix Existing Lists:**

    ```sh
    ./vendor/bin/hippo fix
    ```

    This tidies and optimizes the specified filter file or directory.


## Usage

Hippo provides two main commands: `build` for merging sources and `fix` for optimization.

### Building Filter Lists
Builds multiple source files into a single output file as defined in your `hippo.yml`, including metadata regeneration and stripping unnecessary lines such as comments.

```sh
./vendor/bin/hippo build [options]
```

#### Options:
- `--force`: Ignore cache and rebuild all sources.
- `--config`, `-c`: Use a custom configuration file. Example: `--config ./config.yml`.

### Fixing Filter Lists
Optimizes existing filter files or directories by cleaning syntax, sorting rules, and combining compatible patterns.

```sh
./vendor/bin/hippo fix [options]
```

#### Options:
- `--path`: Target file or directory to process.
- `--force`, `-f`: Process all files regardless of cache.
- `--config`, `-c`: Use a custom configuration file. Example: `--config ./config.yml`.
- `--cache`: Specify a custom cache path. Example: `--cache ./custom-cache/`.


## Configuration

Configure Hippo with a `hippo.yml` file in your project root.

```yaml
cache_dir: .tmp

# Settings for the `fix` command
fixer:
  paths:
    - ./
  ignore:
    - file.txt
    - some/path/to/file.txt
    - path/to/source

# Settings for the `build` command
builder:
  # output_dir: dist
  filter_list:
    # First filter list
    - filename: general_blocklist.txt
      metadata:
        # header: Adblock Plus 2.0
        title: General Blocklist
        # date_modified: false
        # version: true
        # extras: |
        #   Description: Filter list that specifically removes adverts.
        #   Expires: 6 days (update frequency)
        #   Homepage: https://example.org/
        #   License: MIT
      source:
        - blocklists/general/local-rules.txt
        - https://cdn.example.org/blocklists/general.txt

    # Second filter list
    - filename: custom_privacy.txt
      source:
        - sources/tracking_domains-1.txt
        - sources/tracking_domains-2.txt
```

> [!NOTE]
> Lines displaying `# <key>: <value>` are examples of **optional** configuration keys and can be safely removed — Hippo will fall back to its default values.


## Contributing

Contributions are welcome! Please:

1. Fork the repo and create a feature branch.
2. Add tests for new features.
3. Ensure code passes `composer check`.
4. Submit a PR with a clear description.

Report bugs or suggest features via Issues.


## License

This project is licensed under the [MIT License](./LICENSE).
