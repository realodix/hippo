# Configuration

Configuration for this application is handled through the `haiku.yml` file. Below is the documentation for all available options.

## General

#### `cache_dir`
Specifies the directory where the cache file will be stored.


## Fixer
This section configures the behavior for the `fix` command.

##### `paths`
A list of files or directories to be processed. If `fixer.paths` is not set, it defaults to the project's root directory.

##### `excludes`
A list of files or directories to be excluded during processing. If `excludes` contains root paths, Haiku automatically excludes `vendor` directory.

Paths under `excludes` are relative to the `fixer.paths`. Here are some examples of `excludes`, assuming that `src` is defined in `fixer.paths`:
- `Config` will skip the `src/Config` folder.
- `Folder/with/File.txt` will skip `src/Folder/with/File.txt`.


## Builder
This section configures the behavior for the `build` command.

##### `output_dir`
The directory where the compiled filter lists will be saved.

##### `filter_list`
An array that defines one or more filter lists to be built. Each item in the array is an object that configures a single filter list.

- **`filename`** (Required): The output filename for the filter list.
- **`header`**: A multi-line string that will be added at the top of the filter list.
  - `%timestamp%`: Will be replaced with the current date and time in RFC 7231 format.
- **`source`** (Required): A list of source files (local or URL) to build the filter list from.
- **`remove_duplicates`**: If set to `true`, duplicate lines will be removed from the combined filter list.
  - **Possible values:** `true` or `false`
  - **Default:** `false`
