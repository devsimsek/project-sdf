# Libraries Documentation

This is the documentation for the libraries in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Basic Library

A basic library in SDF looks like this:

```php
<?php

class SomeLibrary extends SDF\Library
{
    public function someFunction()
    {
        return 'Hello, World!';
    }
}
```

Let's break down the library:

- `SomeLibrary` - This is the name of the library, you can name it whatever you want.
- `SDF\Library` - This is the base class for the library. You need to extend this class to create a library.
- `someFunction` - This is the name of the function, you can name it whatever you want.
- `return 'Hello, World!';` - This is the output of the library.

For example, let's create a `SomeLibrary` library in `libraries` folder:

```php
<?php

class SomeLibrary extends SDF\Library
{
    public function someFunction()
    {
        return 'Hello, World!';
    }
}
```

In this example, we used the `SomeLibrary` library to create a library. The library has a `someFunction` function that
returns `Hello, World!`.

## Using Libraries in Controllers

You can use libraries in controllers like this:

```php
<?php

class HomeController extends SDF\Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->library('SomeLibrary');
    }

    public function index()
    {
        $library = new SomeLibrary();
        $output = $library->someFunction();
        $this->response->text($output);
    }
}
```

In this example, we used the `SomeLibrary` library in the `HomeController` controller. We loaded the library in the
constructor and used it in the `index` function to get the output of the `someFunction` function.

## Conclusion

In this documentation, we learned how to create and use libraries in SDF. Libraries are a great way to organize your
code and reuse it in different parts of your application.
