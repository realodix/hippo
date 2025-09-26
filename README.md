# Realodix Hippo

Hippo is a powerful command-line tool for managing ad-blocker filter lists. It is designed to automates repetitive tasks such as merging sources, optimize, and tidy up filter lists effortlessly. With a built-in caching system, Hippo skips unchanged files since the last run, resulting in significantly faster processing.


## Features

- **Filter List Compilation:** Seamlessly combine multiple local or remote filter lists into one output file, enriched with customizable metadata.
- **Rule Sorting:** Sorts filter rules alphabetically for consistent and readable ordering.
- **Rules Combining:** Merges rules with identical patterns that differ only by domain, reducing duplication and improving efficiency.
- **Option Sorting:** Sorts filter options (e.g., `$third-party,script,domain=...`) into a standardized and predictable sequence.
- **Caching:** Caches processed files and re-processes only those that have changed, speeding up subsequent runs.
- **Configuration:** Easily configure compiler and fixer behaviors via a simple `hippo.yml` file.

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

1. **Create Configuration:** Add a `hippo.yml` file to your project root (see Configuration for details).
2. **Compile Filters:**

    ```sh
    ./vendor/bin/hippo compile
    ```

    This merges filter sources and generates the output file as defined in your config.

3. **Fix Existing Lists:**

    ```sh
    ./vendor/bin/hippo fix
    ```

    This tidies and optimizes the specified filter file or directory.


## Usage

Hippo provides two main commands: `compile` for merging sources and `fix` for optimization.

### Compiling Filter Lists
Merges multiple source files into a single output file as defined in your `hippo.yml`.

```sh
./vendor/bin/hippo compile [options]
```

#### Options:
- `--force`: Ignore cache and recompile all sources.
- `--config`, `-c`: Use a custom configuration file. Example: `--config ./config.yml`.

### Fixing Filter Lists
Optimizes existing filter files or directories by cleaning syntax, sorting rules, and combining compatible patterns.

```sh
./vendor/bin/hippo fix [options]
```

#### Options:
- `--path`: Target file or directory to process.
- `--force`, `-f`: Process all files regardless of cache.
- `--partial`, `-p`: Partial mode; maps files into several blocks and only processes blocks that are detected as having changed. This mode is very useful for very large files. (incompatible with `--force`).
- `--config`, `-c`: Use a custom configuration file. Example: `--config ./config.yml`.
- `--cache`: Specify a custom cache path. Example: `--cache ./custom-cache/`.


## Configuration

Configure Hippo with a `hippo.yml` file in your project root.

```yaml
cache_dir: .tmp

# Settings for the `fix` command
fixer:
  path: ./
  ignore:
    - file.txt
    - some/path/to/file.txt
    - path/to/source

# Settings for the `compile` command
compiler:
  # output_dir: dist
  filter_list:
    # First output file
    - output_file: General Blocklist
      metadata:
        # header: [Adblock Plus 2.0]
        title: EasyList
        # description: Filter list that specifically removes adverts.
        # expires: 6 days (update frequency)
        # homepage: https://github.com/easylist/easylist
      source:
        - blocklists/general/local-rules.txt
        - https://cdn.example.org/blocklists/general.txt

    # Second output file
    - output_file: custom-privacy.txt
      source:
        - sources/tracking-domains-1.txt
        - sources/tracking-domains-2.txt
```


## Contributing

Contributions are welcome! Please:

1. Fork the repo and create a feature branch.
2. Add tests for new features.
3. Ensure code passes `composer check`.
4. Submit a PR with a clear description.

Report bugs or suggest features via Issues.


## License

This project is licensed under the [MIT License](./LICENSE).
