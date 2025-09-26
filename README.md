# Realodix Hippo

Hippo is a command-line tool for sorting, optimizing, and combining ad-blocker filter lists. The tool can parse ad-blocker filter lists, tidy up the syntax, sort rules, and combine compatible rules to create more efficient and maintainable filter lists. It includes a caching system to avoid reprocessing files that have not changed, making subsequent runs much faster.

## Features

- **Syntax Tidying:** Cleans up filter rule syntax, such as normalizing whitespace.
- **Rule Sorting:** Sorts filter rules alphabetically and by domain.
- **Domain Combining:** Combines multiple rules that differ only by their domain list into a single rule.
- **Option Sorting:** Sorts filter options into a consistent order.
- **Caching:** Caches processed files and only re-processes them if they have changed.

## Installation

Install the package with Composer:

```sh
composer require realodix/hippo
```

## Usage

The main command to process filter lists is `fix`.

```sh
./vendor/bin/hippo fix <path> [options]
```

### Arguments

- `path`: The path to a single filter file or a directory containing multiple filter files.

### Options

- `--ignore`: Specify files or directories to ignore. (Default: `requirements.txt`, `/templates`, `/node_modules`, `/vendor`)
- `--force`, `-f`: Force reprocessing of all files, even if they are cached and haven't changed.
- `--partial`, `-p`: Only process file blocks that have changed since the last run.
- `--cache`: Path to the cache file.
- `--verbose`, `-v`: Show skipped files in the output.

### Examples

#### Processing a single file:

```sh
./vendor/bin/hippo fix /path/to/your/filter.txt
```

#### Processing a directory:

```sh
./vendor/bin/hippo fix /path/to/your/filters/
```

#### Processing a directory and ignoring a sub-directory:

```sh
./vendor/bin/hippo fix /path/to/your/filters/ --ignore=some_subdirectory
```

## How it Works

The tool processes filter lists based on common Ad-block Plus (ABP) syntax. Here are some examples of transformations it performs:

### Combining Domains

Rules with identical patterns but different domains are combined.

```adblock
! Before
-banner-$image,domain=example.com
-banner-$image,domain=example.org

! After
-banner-$image,domain=example.com|example.org
```

### Sorting Options

Filter options are sorted into a consistent order.

```adblock
! Before
||example.com^$script,domain=a.com,third-party

! After
||example.com^$third-party,script,domain=a.com
```

### Tidying Element Hiding Rules

Whitespace and domain lists in element hiding rules are cleaned up.

```adblock
! Before
google.com,example.com## .advert

! After
example.com,google.com##.advert
```
