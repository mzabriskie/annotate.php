annotate.php
============

This project provides Java style annotations for PHP.

## Example

Let's assume we're writing a web request router. We could use annotations to supply the routes and which method is associated.

#### Defining an annotation

```php
<?php

require_once('Annotate.php');

/**
 * @AnnotationTarget(AnnotatedElementType::METHOD)
 */
class Route extends Annotation {}
```

Using the annotation above we can now define a controller and apply the annotation.

```php
<?php

require_once('Annotate.php');

class Controller {
	/**
	 * @Route('/')
	 */
	function home() {}
	
	/**
	 * @Route('/login')
	 */
	function login() {}
}
```

#### Using an annotation

Now that we have defined our annotation and applied it to our controller we can define our web request router.

```php
<?php

require_once('Annotate.php');

class RequestRouter {
	function process($path) {
		$reflect = new AnnotatedReflectionClass('Controller');
        $obj = $reflect->newInstance();
        foreach ($reflect->getMethods() as $method) {
            $route = $method->getAnnotation('Route');
            if ($route->value() == $path) {
                $method->invoke($obj);
                break;
            }
        }
	}
}
```

This is obviously a rudimentary example from the perspective of our <code>RequestRouter</code>, but it shows how the <code>@Route</code> annotation can be put to use.

#### Annotations with multiple values

By default all annotations have a single value called <em>value</em>. Annotations also allow you to define your own values if a single value is not enough.

```php
<?php

require_once('Annotate.php');

/**
 * @AnnotationTarget(AnnotatedElementType::METHOD)
 */
class Route extends Annotation {
	public $path;
	public $method;
	
	public function __construct($path = null, $method = null) {
        $this->path = $path;
        $this->method = $method;
    }

    public function path() {
        return $this->path;
    }

    public function method() {
        return $this->method;
    }
}
```
Now our <code>@Route</code> will allow us to specify a path as well as a request method.

```php
<?php

require_once('Annotate.php');

class Controller {
	/**
	 * @Route('/', 'GET')
	 */
	function home() {}
	
	/**
	 * @Route('/login', 'GET')
	 */
	function login() {}
	
	/**
	 * @Route('/login/auth', 'POST')
	 */
	function loginAuth() {}
}
```
Now our <em>home</em> and <em>login</em> controller methods indicate that they support a <em>GET</em> request method, while <em>loginAuth</em> supports <em>POST</em>. We would obviously need to update our <code>RequestRouter</code> accordingly.

#### Named parameters

If you are providing all the parameters that your annotation expects, or if all the parameters you supply are in the correct order you don't need to do anything special. If you only want to pass some of your parameters however you will need to use named parameters.

```php
<?php

require_once('Annotate.php');

class Controller {
	/**
	 * This route ignores the method parameter
	 * @Route('/foo')
	 */
	function home() {}
	
	/**
	 * This route ignores the path parameter so we have to specify that the
	 * parameter we are passing is meant to be used as the method value.
	 * This is obviously pointless, but for the sake of example...
	 * @Route(method='GET')
	 */
	function bar() {}
}
```
## API Documentation

#### AnnotatedReflectionClass

This class extends [ReflectionClass](http://php.net/manual/en/class.reflectionclass.php) and introduces the following methods:

- getAnnotations
- getAnnotation($annotationClass)
- hasAnnotation($annotationClass)

#### AnnotatedReflectionMethod

This class extends [ReflectionMethod](http://www.php.net/manual/en/class.reflectionmethod.php) and introduces the following methods:

- getAnnotations
- getAnnotation($annotationClass)
- hasAnnotation($annotationClass)

#### AnnotatedReflectionProperty

This class extends [ReflectionProperty](http://www.php.net/manual/en/class.reflectionproperty.php) and introduces the following methods:

- getAnnotations
- getAnnotation($annotationClass)
- hasAnnotation($annotationClass)

#### Annotation

You must extend this class anytime you define your own custom annotation.

#### @AnnotationTarget

This supplied annotation is used to specify the target of annotations. It takes one of the values from <code>AnnotatedElementType</code> as it's <em>value</em>.

#### AnnotatedElementType

You can specify a target to annotation classes and limit what element type they pertain to. The value of the <code>@AnnotationTarget</code> is one of these values:

- ANNOTATION_TYPE - Annotation type declaration
- CONSTRUCTOR - Constructor declaration
- METHOD - Method declaration
- PROPERTY - Property declaration (includes constants)
- TYPE - Class (including annotation type), or interface declaration