# Views Documentation

This is the documentation for the views in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Basic View

A basic view in SDF looks like this:

```html
<h1>Hello, World!</h1>
```

Let's break down the view:

- `<h1>Hello, World!</h1>` - This is the output of the view.

## Creating a View

To create a view, you need to create a file in the `app/views` directory. The file should have a `.php` extension.

Here is an example of a view file:

```html
<!-- app/views/home.php -->
<h1>Hello, World!</h1>
```

-- or --

You can use the cli to create a view:

```bash
./sdf/cli g view home
```

This will create a view file in the `app/views` directory with the name `home.php`.

## Using php in Views

You can use php in views like this:

```html
<h1><?php echo 'Hello, World!'; ?></h1>
```

## Using Fuse in Views

> Note: `USE_FUSE` must be set to true in the configuration file to use Fuse.

You can use Fuse in views like this:

```html
<h1>{{ 'Hello, World!' }}</h1>
```

> For more information on Fuse, see the [Fuse Documentation](../libraries/fuse.md).

## Using Views in Controllers

You can use views in controllers like this:

```php
<?php

class Home extends SDF\Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->view('header');
    }

    public function index() {
        $this->load->view('home');
    }
}
```

In this example, we used the `Home` controller to create a controller. The controller has an `index` function that
loads the `header` and `home` views.

## Conclusion

In this documentation, we learned how to create views in SDF, how to use php and Fuse in views, and how to use views in
controllers.
