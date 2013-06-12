<?php

require_once(dirname(__FILE__) . '/../src/Annotate.php');

/**
 * @AnnotationTarget(AnnotatedElementType::CONSTRUCTOR)
 */
class AnnotateTestConstructorAnnotation extends Annotation {}

/**
 * @AnnotationTarget(AnnotatedElementType::METHOD)
 */
class AnnotateTestMethodAnnotation extends Annotation {}

/**
 * @AnnotationTarget(AnnotatedElementType::PROPERTY)
 */
class AnnotateTestPropertyAnnotation extends Annotation {}

/**
 * @AnnotationTarget(AnnotatedElementType::TYPE)
 */
class AnnotateTestTypeAnnotation extends Annotation {}

/**
 * @AnnotationTarget(AnnotatedElementType::PROPERTY)
 */
class AnnotateTestAnnotationValues extends Annotation {
    public $foo;
    public $bar;

    public function __construct($foo = null, $bar = null) {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public function foo() {
        return $this->foo;
    }

    public function bar() {
        return $this->bar;
    }
}

interface ConstValueHolder {
    const FOO = 1;
    const BAR = 2;
}

/**
 * @AnnotateTestTypeAnnotation
 */
class AnnotateTest extends TestCase {

	private $reflect;

	/**
	 * @AnnotateTestPropertyAnnotation
	 */
	private $property;

	/**
	 * This is annotated incorrectly for the purpose of testing
	 * @AnnotateTestMethodAnnotation
	 */
	private $propertyForFailure;

    /**
     * @AnnotateTestAnnotationValues
     */
    private $propertyForAnnotationValuesNoArgs;

    /**
     * @AnnotateTestAnnotationValues('This is foo', 'This is bar')
     */
    private $propertyForAnnotationValuesArgs;

    /**
     * @AnnotateTestAnnotationValues(bar='abc', foo=123)
     */
    private $propertyForAnnotationValuesProps;

    /**
     * @AnnotateTestAnnotationValues(ConstValueHolder::FOO, ConstValueHolder::BAR)
     */
    private $propertyForAnnotationValuesConst;

	/**
	 * @AnnotateTestConstructorAnnotation
	 */
	function __construct() {}

	function setUp() {
		parent::setUp();

		$this->reflect = new AnnotatedReflectionClass(get_class($this));
	}

	function tearDown() {
		$this->reflect = null;

		parent::tearDown();
	}

	/**
	 * @AnnotateTestMethodAnnotation
	 */
	function testAnnotationTypeTarget() {
		// 1. Test that valid targets are correctly annotated
		$this->assertTrue($this->reflect->hasAnnotation('AnnotateTestTypeAnnotation'));
		$this->assertTrue($this->reflect->getMethod('__construct')->hasAnnotation('AnnotateTestConstructorAnnotation'));
		$this->assertTrue($this->reflect->getMethod('testAnnotationTypeTarget')->hasAnnotation('AnnotateTestMethodAnnotation'));
		$this->assertTrue($this->reflect->getProperty('property')->hasAnnotation('AnnotateTestPropertyAnnotation'));

		// 2. Make sure parser catches invalid target 
		$error = false;
		try {
			$this->reflect->getProperty('propertyForFailure')->hasAnnotation('AnnotateTestMethodAnnotation');
		}
		catch (AnnotationTargetException $e) {
			$error = true;
		}

		$this->assertTrue($error, 'Parser didn\'t catch invalid target');
	}

	/**
	 * @throws AssertionError
	 */
	function testDocTags() {
		// 1. Make sure PHP Doc tags aren't matched as an annotation
		$this->assertFalse($this->reflect->getMethod('testDocTags')->hasAnnotation('throws'));
	}

	function testArguments() {
		// 1. Make sure no args works correctly
        $this->assertNull($this->reflect->getProperty('propertyForAnnotationValuesNoArgs')->getAnnotation('AnnotateTestAnnotationValues')->foo());
        $this->assertNull($this->reflect->getProperty('propertyForAnnotationValuesNoArgs')->getAnnotation('AnnotateTestAnnotationValues')->bar());

        // 2. Make sure constructor arguments work correctly
        $this->assertEquals('This is foo', $this->reflect->getProperty('propertyForAnnotationValuesArgs')->getAnnotation('AnnotateTestAnnotationValues')->foo());
        $this->assertEquals('This is bar', $this->reflect->getProperty('propertyForAnnotationValuesArgs')->getAnnotation('AnnotateTestAnnotationValues')->bar());

        // 3. Make sure property values work correctly
        $this->assertEquals(123, $this->reflect->getProperty('propertyForAnnotationValuesProps')->getAnnotation('AnnotateTestAnnotationValues')->foo());
        $this->assertEquals('abc', $this->reflect->getProperty('propertyForAnnotationValuesProps')->getAnnotation('AnnotateTestAnnotationValues')->bar());

        // 4. Make sure constant values work correctly
        $this->assertEquals(ConstValueHolder::FOO, $this->reflect->getProperty('propertyForAnnotationValuesConst')->getAnnotation('AnnotateTestAnnotationValues')->foo());
        $this->assertEquals(ConstValueHolder::BAR, $this->reflect->getProperty('propertyForAnnotationValuesConst')->getAnnotation('AnnotateTestAnnotationValues')->bar());
	}

}
