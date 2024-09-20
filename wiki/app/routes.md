# Routes Documentation

This is the documentation for the routes in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

## Basic Route

A basic route in SDF looks like this:

```php
<?php
$config['/'] = 'Home';
```

Let's break down the route:

- `/` - This is the URL of the route, you can name it whatever you want.
- `Home` - This is the controller of the route, you can name it whatever you want. If the method is left empty, it will
  default to `Home/index`.

## Defining Method Based Routes

You can define method based routes like this:

```php
<?php
$config['/'] = 'Home/index';
$config['/about'] = 'Home/about';
$config['/get'] = ['Home/get', 'GET'];
$config['/post'] = ['Home/post', 'POST'];
$config['/put'] = ['Home/put', 'PUT'];
$config['/delete'] = ['Home/delete', 'DELETE'];
```

In this example, we defined a route for each method. The first two routes are for `GET` requests, the third route is for
`POST` requests, the fourth route is for `PUT` requests, and the last route is for `DELETE` requests.

## Defining Dynamic Routes

You can define dynamic routes like this:

```php
<?php
$config['/user/{id}'] = 'User/get';
```

In this example, we defined a dynamic route for the `User/get` method. The `{id}` part is a placeholder for the user ID.

Method get from the User controller will be called with the user ID as a parameter. Therefor, the method signature
should
look like this:

```php
<?php

class User extends SDF\Controller
{

    // ...

    public function get($id)
    {
        // Do something with the user ID
    }
}
```

## Conclusion

In this documentation, we learned how to define routes in SDF. We covered basic routes, method based routes, and dynamic
routes. Routes are a great way to define the structure of your application and handle different types of requests.
