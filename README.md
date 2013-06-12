php-annotate
============

Annotations for PHP

	<?php
	
	require_once('Annotate.php');

	/**
	 * @AnnotationTarget(AnnotatedElementType::TYPE)
	 */
	class ExampleTypeAnnotation extends Annotation {}

	/**
	 * @AnnotationTarget(AnnotatedElementType::METHOD)
	 */
	class ExampleMethodAnnotation extends Annotation {}

	/**
	 * @ExampleTypeAnnotation
	 */
	class ExampleClass {
		/**
		 * @ExampleMethodAnnotation('bar')
		 */
		function foo() {}
	}

	$reflectionClass = new AnnotatedReflectionClass('ExampleClass');
	if ($reflectionClass->hasAnnotation('ExampleTypeAnnotation')) {
		echo 'ExampleClass has ExampleTypeAnnotation present' . "\n";
	}

	$reflectionMethod = $reflectionClass->getMethod('foo');
	if ($reflectionMethod->hasAnnotation('ExampleMethodAnnotation')) {
		echo 'ExampleClass::foo has ExampleMethodAnnotation present with value of "' . $reflectionMethod->getAnnotation('ExampleMethodAnnotation')->value() . '"' . "\n";
	}