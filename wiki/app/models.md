# Models Documentation

This is the documentation for the models in the app. Here you can find all the information you need to get started.

> I am currently working on the documentation. If you have any questions, feel free to reach out to me
> on [Twitter](https://x.com/devsimsek).

> Note: SDF currently uses Sorm\Model as the base class for the models. This documentation only covers the basic
> implementation of SDF\Model not SDF\Sorm\Model.
> If you want to use SDF\Sorm\Model, you can find the documentation under [Sorm Library](libraries/sorm.md#model).

## Basic Model

A basic model in SDF looks like this:

```php
<?php

class SomeModel extends SDF\Model
{
    public function someFunction()
    {
        return 'Hello, World!';
    }
}
```

Let's break down the model:

- `SomeModel` - This is the name of the model, you can name it whatever you want.
- `SDF\Model` - This is the base class for the model. You need to extend this class to create a model.
- `someFunction` - This is the name of the function, you can name it whatever you want.
- `return 'Hello, World!';` - This is the output of the model.

For example, let's create a `SomeModel` model in `models` folder:

```php
<?php

class SomeModel extends SDF\Model
{
    public function someFunction()
    {
        return 'Hello, World!';
    }
}
```

In this example, we used the `SomeModel` model to create a model. The model has a `someFunction` function that
returns `Hello, World!`.

## Using Models in Controllers

You can use models in controllers like this:

```php
<?php

class HomeController extends SDF\Controller
{
    protected $SomeModel;

    public function __construct() {
        parent::__construct();
        $this->load->model('SomeModel');
        $this->SomeModel = new SomeModel();
    }

    public function index()
    {
        $data['message'] = $this->SomeModel->someFunction();
        $this->load->view('home', $data);
    }
}
```

In this example, we used the `SomeModel` model to create a model. The model has a `someFunction` function that
returns `Hello, World!`. We then used the model in the `HomeController` controller to get the message and pass it to the
view.

## Conclusion

This is the basic usage of models in SDF. You can create models to interact with your database or perform other
operations. Models are a great way to separate your business logic from your controllers and keep your code clean and
organized.
