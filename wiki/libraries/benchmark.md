# SDF Benchmark Documentation

## Overview

The `Benchmark` class is part of the **SDF Core** library. It provides functionality for tracking execution time and
memory usage within an application. Developers can mark specific points in the code and calculate the time elapsed
between them, making it useful for performance analysis and debugging.

## Class: `Benchmark`

This class extends the `Core` class and offers methods for marking timepoints in the code and calculating the time
elapsed between them. It also provides a placeholder for memory usage.

### Properties

- **`$marker`** (array): An associative array that stores benchmark markers with timestamps.

### Methods

#### `mark(string $name): void`

Sets a benchmark marker at the current point in time, allowing for multiple markers to be placed throughout the code.

- **Parameters:**
  - `string $name`: The name of the marker (e.g., `"start"` or `"end"`).

- **Returns:** `void`

- **Example:**
  ```php
  $this->benchmark->mark('start');
  // some code to benchmark
  $this->benchmark->mark('end');
  ```

#### `elapsed_time(string $point1 = "", string $point2 = "", int $decimals = 4): string`

Calculates the elapsed time between two markers. If `point2` is not provided, the current time will be used as the
endpoint. If `point1` is empty, the method returns `{elapsed_time}`, which can be used as a placeholder in templates.

- **Parameters:**
  - `string $point1`: The name of the first marker (starting point).
  - `string $point2`: The name of the second marker (ending point). If not provided, the current time is used.
  - `int $decimals`: The number of decimal places to display (default is 4).

- **Returns:** `string`
  - The formatted elapsed time between the two points.
  - `{elapsed_time}` if `point1` is empty.
  - An empty string if `point1` is not found.

- **Example:**
  ```php
  $this->benchmark->mark('start');
  // some code execution
  echo $this->benchmark->elapsed_time('start', 'end'); // Outputs the time elapsed
  ```

#### `memory_usage(): string`

Returns the `{memory_usage}` placeholder, which will be swapped out for the actual memory usage by the output class when
rendering templates. This allows memory usage to be calculated at the end of execution.

- **Returns:** `string`
  - `{memory_usage}` as a placeholder in the output.

- **Example:**
  ```php
  echo $this->benchmark->memory_usage(); // Outputs {memory_usage} in the template
  ```

### Error Handling

- If `point1` is not found in the `$marker` array, the `elapsed_time` method returns an empty string.
- If `point2` is not provided, the current time is automatically marked for calculating the time difference
  with `point1`.

### Use Cases

1. **Benchmarking Code Performance:**
   By placing `mark()` calls around sections of code, you can easily track how long certain operations take to complete.

2. **Memory Usage in Templates:**
   Using the `{memory_usage}` placeholder, memory usage can be inserted into templates, providing insight into the
   resource footprint of different parts of the application.
