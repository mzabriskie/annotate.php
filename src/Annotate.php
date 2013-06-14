<?php

/*

Copyright (c) 2013 by Matt Zabriskie

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

abstract class Annotation {
	public $value;

	function __construct($value = null) {
		$this->value = $value;
	}

	function value() {
		return $this->value;
	}
}

/**
 * @AnnotationTarget(value=AnnotatedElementType::ANNOTATION_TYPE)
 */
class AnnotationTarget extends Annotation {
	function value() {
		if ($this->value != null && is_array($this->value)) {
			$bitmask = 0;
			foreach ($this->value as $bit) {
				$bitmask &= $bit;
			}
			$this->value = $bitmask;
		}

		return $this->value;
	}
}

interface AnnotatedElementType {
	/**
	 * Annotation type declaration
	 */
	const ANNOTATION_TYPE = 1;

	/**
	 * Constructor declaration
	 */
	const CONSTRUCTOR = 2;

	/**
	 * Method declaration
	 */
	const METHOD = 4;

	/**
	 * Property declaration (includes constants)
	 */
	const PROPERTY = 8;

	/**
	 * Class (including annotation type), or interface declaration
	 */
	const TYPE = 16;
}

interface AnnotatedElement {
	function getAnnotatedElementType();

	function getAnnotations();

	function getAnnotation($annotationClass);

	function hasAnnotation($annotationClass);

	function getDocComment();
}

class AnnotatedReflectionClass extends ReflectionClass implements AnnotatedElement {

	function getAnnotatedElementType() {
		return (AnnotatedElementType::TYPE | ($this->isSubClassOf('Annotation') ? AnnotatedElementType::ANNOTATION_TYPE : 0));
	}

	private $annotations;

	function getAnnotations() {
		if ($this->annotations == null) {
			$this->annotations = AnnotationParser::parse($this);
		}
		return $this->annotations;
	}

	function getAnnotation($annotationClass) {
		$all = $this->getAnnotations();
		return (isset($all[$annotationClass]) ? $all[$annotationClass] : null);
	}

	function hasAnnotation($annotationClass) {
		return $this->getAnnotation($annotationClass) != null;
	}

	private $methods;

	function getMethods() {
		if ($this->methods == null) {
			$methods = parent::getMethods();
			$this->methods = array();
			foreach ($methods as $method) {
				$this->methods[$method->getName()] = new AnnotatedReflectionMethod($this->getName(), $method->getName());
			}
		}
		return $this->methods;
	}

	function getMethod($name) {
		$all = $this->getMethods();
		return (isset($all[$name]) ? $all[$name] : null);
	}

	function hasMethod($name) {
		return $this->getMethod($name) != null;
	}

	private $properties;

	function getProperties() {
		if ($this->properties == null) {
			$properties = parent::getProperties();
			$this->properties = array();
			foreach ($properties as $property) {
				$this->properties[$property->getName()] = new AnnotatedReflectionProperty($this->getName(), $property->getName());
			}
		}
		return $this->properties;
	}

	function getProperty($name) {
		$all = $this->getProperties();
		return (isset($all[$name]) ? $all[$name] : null);
	}

	function hasProperty($name) {
		return $this->getProperty($name) != null;
	}

}

class AnnotatedReflectionMethod extends ReflectionMethod implements AnnotatedElement {

	function getAnnotatedElementType() {
		return (AnnotatedElementType::METHOD | ($this->isConstructor() ? AnnotatedElementType::CONSTRUCTOR : 0));
	}

	private $annotations;

	function getAnnotations() {
		if ($this->annotations == null) {
			$this->annotations = AnnotationParser::parse($this);
		}
		return $this->annotations;
	}

	function getAnnotation($annotationClass) {
		$all = $this->getAnnotations();
		return (isset($all[$annotationClass]) ? $all[$annotationClass] : null);
	}

	function hasAnnotation($annotationClass) {
		return $this->getAnnotation($annotationClass) != null;
	}

}

class AnnotatedReflectionProperty extends ReflectionProperty implements AnnotatedElement {

	function getAnnotatedElementType() {
		return AnnotatedElementType::PROPERTY;
	}

	private $annotations;

	function getAnnotations() {
		if ($this->annotations == null) {
			$this->annotations = AnnotationParser::parse($this);
		}
		return $this->annotations;
	}

	function getAnnotation($annotationClass) {
		$all = $this->getAnnotations();
		return (isset($all[$annotationClass]) ? $all[$annotationClass] : null);
	}

	function hasAnnotation($annotationClass) {
		return $this->getAnnotation($annotationClass) != null;
	}

}

class AnnotationParseException extends Exception {}

class AnnotationTargetException extends Exception {}

class AnnotationParser {
	// Tags used for generating PHP Docs (http://www.phpdoc.org/)
	private static $TAGS = array('@abstract', '@access', '@author',
		'@copyright', '@deprecated', '@deprec', '@example', '@exception',
		'@global', '@ignore', '@internal', '@param', '@return', '@link',
		'@name', '@magic', '@package', '@see', '@since', '@static',
		'@staticvar', '@subpackage', '@throws', '@todo', '@var', '@version');

	/**
	 * Parses an element's DocComment for Annotations
	 *
	 * @param AnnotatedElement $element Element to parse DocComment for
	 * @return array
     * @throws AnnotationParseException
	 */
	static function parse(AnnotatedElement $element) {
		$annotations = array();
		$comment = $element->getDocComment();

		// Parsing is only required if comment is present
		if (strlen(trim($comment)) > 0) {
			$matches = array();

			// Find all annotations (this may include PHP Doc tags)
			preg_match_all('/@(.*)[\n|\*]/', $comment, $matches);
			foreach ($matches[1] as $match) {
				$match = trim($match);
				$args = null;
				$props = null;

				// Annotation with parameters
				if (strpos($match, '(') > 0) {
					$parts = array();
					preg_match_all('/^(.*?)\((.*?)\)/', $match, $parts);
					$name = $parts[1][0];

					// Don't process any further if annotation is a PHP Doc tag
					if (in_array('@' . $name, self::$TAGS)) continue;

					$tmp = array();
					preg_match_all('/(\[)?(.*?)(?(1)\]|(?:, ?|$))/', $parts[2][0], $tmp);

					foreach ($tmp[2] as $a) {
						if (strlen(trim($a)) == 0) continue;

						// Named properties
						if (strpos($a, '=') > 0) {
							$kv = explode('=', $a);
							$props[trim($kv[0])] = self::value($kv[1]);
						}
						// Constructor arguments
						else {
							$args[] = self::value($a);
						}
					}

					if (sizeof($args) > 0 && sizeof($props) > 0) {
						throw new AnnotationParseException('Annotation "' . $name . '" cannot use both named properties and constructor arguments');
					}
				}
				// No parameters
				else {
					$name = trim($match);

					// Don't process any further if annotation is a PHP Doc tag
					if (in_array('@' . $name, self::$TAGS)) continue;
				}

				$result = self::create($element, $name, $args, $props);
				if ($result != null) {
					$annotations[$name] = $result;
				}
			}
		}

		return $annotations;
	}

	/**
	 * Evaluates the value of an argument or property
	 *
	 * @param string $val Value to be evaluated
	 * @return mixed
	 */
	private static function value($val) {
		$val = trim($val);

		// array
		if (strpos($val, ',') > 0) {
			$val = explode(',', $val);
			foreach ($val as $idx => $tmp) {
				$val[$idx] = self::value($tmp);
			}
		}
		// string
		else if (preg_match('/^([\'"]).*([\'"])$/', $val)) {
			$val = substr($val, 1);
			$val = substr($val, 0, strlen($val) -1);
		}
		// evaluable (int, boolean, constant, etc.)
		else {
			eval('$val = ' . $val . ';');
		}

		return $val;
	}

	/**
	 * Creates an instance of the specified Annotation
	 *
	 * @param AnnotatedElement $element Element that Annotation was declared for
	 * @param string $name Name of the Annotation class
	 * @param array $args Arguments to pass to the Annotation constructor
	 * @param array $props Properties to populate the Annotation with
	 * @return Annotation
     * @throws AnnotationParseException
	 */
	private static function create(AnnotatedElement $element, $name, $args = null, $props = null) {
		$result = null;

		// Ensure that the class exists
		if (class_exists($name)) {
			$class = new AnnotatedReflectionClass($name);

			// Ensure that class is a subclass of Annotation
			if ($class->isSubClassOf('Annotation')) {
				// Validate annotation target
				self::validate($element, $class);

				// Instantiate annotation with constructor arguments
				$result = $class->newInstanceArgs($args == null ? array() : $args);

				// Populate annotation properties
				if (is_array($props) && sizeof($props) > 0) {
					foreach ($props as $key => $val) {
						if ($class->getProperty($key) == null) {
							throw new AnnotationParseException('Invalid property ' . $key . ' for Annotation ' . $name);
						}
						$result->$key = $val;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Validates that an element is an acceptable target for an Annotation
	 * 
	 * @param AnnotatedElement $element
	 * @param AnnotatedReflectionClass $class
	 * @return void
     * @throws AnnotationTargetException
	 */
	private static function validate(AnnotatedElement $element, AnnotatedReflectionClass $class) {
		if ($element->getName() == 'AnnotationTarget') return;

		if ($class->hasAnnotation('AnnotationTarget')) {
			$target = $class->getAnnotation('AnnotationTarget');
			if ($target->value() != null && $target->value() > 0 &&
				!(($target->value() & $element->getAnnotatedElementType()) == $target->value())) {
				throw new AnnotationTargetException('Invalid annotation "' . $class->getName() . '" for "' . $element->getName() . '"');
			}
		}
	}
}
