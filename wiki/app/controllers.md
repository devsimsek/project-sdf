# Controllers Documentation

This is the documentation for the controllers in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Basic Controller

A basic controller in SDF looks like this:

```php
<?php

class Home extends SDF\Controller
{
    public function __construct() {
        parent::__construct(); // This is required to load the predefined methods, some php versions require this.
    }

    public function index()
    {
        echo 'Hello, World!';
    }
}
```

Let's break down the controller:

- `Home` - This is the name of the controller. The controller name should be the same as the file name.
- `SDF\Controller` - This is the base controller class. All controllers should extend this class.
- `__construct()` - This is the constructor method. This is required to load the predefined methods, some php versions
  require this.
- `index()` - This is the default method. This method is called when no method is specified in the URL.
- `echo 'Hello, World!';` - This is the output of the method.

Let's use `Response` class to output the message:

```php
<?php

class Home extends SDF\Controller
{
    public function __construct() {
        parent::__construct(); // This is required to load the predefined methods, some php versions require this.
    }

    public function index()
    {
        $this->response->text('Hello, World!');
    }
}
```

In this example, we used the `text()` method of the `Response` class to output the message.

## Methods

You can define your methods in the controller as follows:

```php
<?php

class Home extends SDF\Controller
{
    public function __construct() {
        parent::__construct(); // This is required to load the predefined methods, some php versions require this.
    }

    public function index()
    {
        $this->response->text('Hello, World!');
    }

    public function about()
    {
        $this->response->text('This is the about page.');
    }
}
```

In this example, we defined an `about()` method in the controller.

## Parameters

You can pass parameters to the controller methods as follows:

```php
<?php

class Home extends SDF\Controller
{
    public function __construct() {
        parent::__construct(); // This is required to load the predefined methods, some php versions require this.
    }

    public function index()
    {
        $this->response->text('Hello, World!');
    }

    public function about($name)
    {
        $this->response->text('Hello, ' . $name);
    }
}
```

and routes.php as follows:

```php
<?php
$config['/'] = 'Home/index';
$config['/about/{name}'] = 'Home/about';
```

In this example, we defined a route with a parameter `{name}` and passed it to the `about()` method in the controller.

## Conclusion

In this section, you learned how to create controllers in SDF. You learned about the basic controller, methods, and
parameters.

> For detailed information on controller properties and methods, check the core [Controller](sdf/core.md#controllers)
> documentation.
