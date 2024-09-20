# SDF Fuse View Engine Documentation

## Overview

The **Fuse** class is a custom view engine for the SDF framework, designed to handle the rendering of view files and
manage view data. It supports custom directives like `@Foreach`, `@If`, `@For`, and `@While` as well as variable
interpolation using `{{ variable }}` syntax.

## Class: `Fuse`

This class is part of the SDF framework and is used to assign data to views, render views, and parse template content.

> Fuse is handled by the core and does not need to be loaded or used directly in controllers.
> We do not recommend using this class directly unless you are extending the core/renderer functionality.

### Properties

- **`$data`** (array): Stores the data passed to the view for rendering.

### Methods

#### `__construct()`

Initializes the Fuse engine and defines the `SDF` and `SDF_APP_VIEW` constants if they are not already set.

- **Returns:** `void`

- **Example:**
  ```php
  $fuse = new Fuse();
  ```

#### `with(mixed $data, string $key = null): self`

Assigns data to the view. If a key is provided, the data will be stored under that key; otherwise, it merges the new
data with the existing data array.

- **Parameters:**
  - `mixed $data`: The data to assign to the view.
  - `string|null $key`: Optional key to store the data under.

- **Returns:** `self`

- **Example:**
  ```php
  $fuse->with(['name' => 'John']);
  $fuse->with('John', 'name');
  ```

#### `render(string $view, string $path = SDF_APP_VIEW): string|false`

Renders the view file. It resolves the view file, reads its content, parses it, and then extracts the data for
rendering. The output is captured and returned.

- **Parameters:**
  - `string $view`: The view file name (without extension).
  - `string $path`: Optional directory path to the view files (defaults to `SDF_APP_VIEW`).

- **Returns:** `string|false`: Rendered content as a string or `false` on failure.

- **Throws:**
  - `\Exception` if the view file is not found.

- **Example:**
  ```php
  echo $fuse->render('homepage');
  ```

#### `resolveView(string $view, string $path): string|false`

Resolves the correct view file by checking for supported extensions (`.php`, `.phtml`, `.fuse`). It throws an exception
if the view file is not found.

- **Parameters:**
  - `string $view`: The view file name.
  - `string $path`: Directory path of the view file.

- **Returns:** `string|false`: The resolved view file name or `false` if not found.

- **Throws:**
  - `\Exception` if the view file does not exist.

- **Example:**
  ```php
  $resolvedView = $fuse->resolveView('homepage', '/path/to/views/');
  ```

#### `parseContent(string $input): string`

Parses the content of the view file, converting custom template directives into PHP code. Supported directives
include `@Foreach`, `@If`, `@For`, `@While`, and variable interpolation with `{{ variable }}`.

- **Parameters:**
  - `string $input`: The raw content of the view file.

- **Returns:** `string`: The parsed content with PHP directives.

- **Example:**
  ```php
  $parsedContent = $fuse->parseContent($rawViewContent);
  ```

### Custom Directives

#### `@Foreach`

Converts `@Foreach` directives to PHP `foreach` loops.

- **Syntax:**
  ```php
  @Foreach($array as $item)
    <p>{{ $item }}</p>
  @endForeach
  ```

- **Equivalent PHP:**
  ```php
  <?php foreach ($array as $item): ?>
    <p><?php echo htmlspecialchars($item, ENT_QUOTES); ?></p>
  <?php endforeach; ?>
  ```

#### `@If` / `@Else` / `@ElseIf`

Handles conditional logic within the view template.

- **Syntax:**
  ```php
  @If($condition)
    <p>Condition is true</p>
  @Else
    <p>Condition is false</p>
  @endIf
  ```

- **Equivalent PHP:**
  ```php
  <?php if ($condition): ?>
    <p>Condition is true</p>
  <?php else: ?>
    <p>Condition is false</p>
  <?php endif; ?>
  ```

#### `@For`

Converts `@For` directives to PHP `for` loops.

- **Syntax:**
  ```php
  @For($i = 0; $i < 10; $i++)
    <p>{{ $i }}</p>
  @endFor
  ```

- **Equivalent PHP:**
  ```php
  <?php for ($i = 0; $i < 10; $i++): ?>
    <p><?php echo htmlspecialchars($i, ENT_QUOTES); ?></p>
  <?php endfor; ?>
  ```

#### `@While`

Converts `@While` directives to PHP `while` loops.

- **Syntax:**
  ```php
  @While($condition)
    <p>Looping...</p>
  @endWhile
  ```

- **Equivalent PHP:**
  ```php
  <?php while ($condition): ?>
    <p>Looping...</p>
  <?php endwhile; ?>
  ```

#### `{{ variable }}`

Interpolates PHP variables into the template with automatic escaping for security.

- **Syntax:**
  ```php
  <p>{{ $name }}</p>
  ```

- **Equivalent PHP:**
  ```php
  <p><?php echo htmlspecialchars($name, ENT_QUOTES); ?></p>
  ```

#### `@var`

Allows direct PHP variable assignment in the template.

- **Syntax:**
  ```php
  @var $count = 10;
  ```

- **Equivalent PHP:**
  ```php
  <?php $count = 10; ?>
  ```

### Error Handling

The `Fuse` class throws exceptions if a view file is not found or cannot be resolved. This ensures that the developer is
notified of missing files during rendering.
