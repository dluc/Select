<?php
/**
 * Copyright 2011 Devis Lucato <http://lucato.it>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require FIXTURES . 'select_OldNS.php';
require FIXTURES . 'select_NewNS.php';

class Select_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        App_Builder::reset();
        App\Builder::reset();
    }
    
    /**
     * We have three DI containers providing the same features, one with "old namespaces"
     * and two with the actual namespace.  All are tested.
     * @wtf http://j.mp/paamayim-nekudotayim
     */
    public function classUnderTest()
    {
        return array(
            array('App_Builder', 'App_'),
            array('App\Builder', 'App\\'),
        );
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Provides_a_full_class_name($classUnderTest, $currentNamespace)
    {
        $this->assertSame($currentNamespace . 'Apple', $classUnderTest::getClass('Apple'));
        $this->assertSame($currentNamespace . 'Numbers', $classUnderTest::getClass($currentNamespace . 'Numbers'));
        $this->assertSame('XSLTProcessor', $classUnderTest::getClass('XSLTProcessor'));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Change_old_namespace($classUnderTest, $currentNamespace)
    {
        $classUnderTest::setNamespace('Food_Fruit_');
        $this->assertSame('Food_Fruit_Strawberry', $classUnderTest::getClass('Strawberry'));
        
        if (!class_exists('Food_Fruit_Strawberry')) eval("class Food_Fruit_Strawberry { }");
        $this->assertSame('Food_Fruit_Strawberry', get_class($classUnderTest::create('Strawberry')));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Change_new_namespace($classUnderTest, $currentNamespace)
    {
        $namespace = "Europe\\";
        
        $classUnderTest::setNamespace($namespace);
        $this->assertSame($namespace . 'Italy', $classUnderTest::getClass('Italy'));
        
        require FIXTURES . 'europe.php';
        $this->assertSame($namespace . 'Italy', get_class($classUnderTest::create('Italy')));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Restore_original_namespace($classUnderTest, $currentNamespace)
    {
        $classUnderTest::setNamespace('StarTrek_');
        $this->assertSame('StarTrek_Movie', $classUnderTest::getClass('Movie'));
        
        $classUnderTest::resetNamespace();
        $this->assertSame("{$currentNamespace}Movie", $classUnderTest::getClass('Movie'));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Replace_a_class($classUnderTest, $currentNamespace)
    {
        $classUnderTest::replaceClass('Tomato', '\Parch\Sauce');
        $this->assertSame('\Parch\Sauce', $classUnderTest::getClass('Tomato'));
        
        $classUnderTest::replaceClass('Mail', 'A_Fake_Mailer');
        $this->assertSame('A_Fake_Mailer', $classUnderTest::getClass('Mail'));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Restore_original_class($classUnderTest, $currentNamespace)
    {
        $classUnderTest::replaceClass('HTTPClient', $currentNamespace . 'FakeHTTPClient');
        $this->assertSame($currentNamespace . 'FakeHTTPClient', $classUnderTest::getClass('HTTPClient'));
        
        $classUnderTest::resetClass('HTTPClient');
        $this->assertSame($currentNamespace . 'HTTPClient', $classUnderTest::getClass('HTTPClient'));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Restore_original_classes($classUnderTest, $currentNamespace)
    {
        $classUnderTest::replaceClass('HTTPClient', $currentNamespace . 'FakeHTTPClient');
        $this->assertSame($currentNamespace . 'FakeHTTPClient', $classUnderTest::getClass('HTTPClient'));
        
        $classUnderTest::replaceClass('Mailer', $currentNamespace . 'FakeMailer');
        $this->assertSame($currentNamespace . 'FakeMailer', $classUnderTest::getClass('Mailer'));
        
        $classUnderTest::resetClasses();
        $this->assertSame($currentNamespace . 'HTTPClient', $classUnderTest::getClass('HTTPClient'));
        $this->assertSame($currentNamespace . 'Mailer', $classUnderTest::getClass('Mailer'));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Reset_status($classUnderTest, $currentNamespace)
    {
        $classUnderTest::setNamespace('Food_Fruit_');
        $classUnderTest::replaceClass('Apple', 'Food_Fruit_Orange');
        
        $this->assertSame('Food_Fruit_Peach', $classUnderTest::getClass('Peach'));
        $this->assertSame('Food_Fruit_Orange', $classUnderTest::getClass('Apple'));
        
        $classUnderTest::setProcessor(function() { return $classUnderTest::create('XSLTProcessor'); });
        
        $classUnderTest::reset();
        
        $this->assertSame($currentNamespace . 'Peach', $classUnderTest::getClass('Peach'));
        $this->assertSame($currentNamespace . 'Apple', $classUnderTest::getClass('Apple'));
        
        try {
            $classUnderTest::getProcessor();
        } catch (Select_MissingDependency_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_without_parameters($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers');
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_with_1_parameter($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers', 1);
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(1), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_with_2_parameters($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers', 1, 2);
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(1, 2), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_with_3_parameters($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers', 1, 2, 3);
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(1, 2, 3), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_with_4_parameters($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers', 1, 2, 3, 4);
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(1, 2, 3, 4), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_with_5_parameters($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers', 1, 2, 3, 4, 5);
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(1, 2, 3, 4, 5), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_object_with_6_parameters($classUnderTest, $currentNamespace)
    {
        $o = $classUnderTest::create('Numbers', 1, 2, 3, 4, 5, 6);
        $this->assertSame($currentNamespace . 'Numbers', get_class($o));
        $this->assertSame(array(1, 2, 3, 4, 5, 6), $o->series);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Too_many_parameters_cause_exception($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::create('Numbers', 1, 2, 3, 4, 5, 6, 7);
        } catch (Select_TooManyParameters_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Get_external_class($classUnderTest, $currentNamespace)
    {
        $this->assertSame('PHPUnit_Framework_TestCase', $classUnderTest::getClass('PHPUnit_Framework_TestCase'));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Missing_class_causes_exception($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::create('Orange');
        } catch (Select_InvalidClass_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Replace_dependency_with_DEF($classUnderTest, $currentNamespace)
    {
        $classUnderTest::defProcessor('XSLTProcessor');
        $this->assertSame('XSLTProcessor', get_class($classUnderTest::getProcessor()));
        
        $classUnderTest::defProcessor('ArrayIterator', array(array()));
        $this->assertSame('ArrayIterator', get_class($classUnderTest::getProcessor()));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Replace_dependency_with_SET($classUnderTest, $currentNamespace)
    {
        $dependency = $classUnderTest::create('XSLTProcessor');
        $replacement = $classUnderTest::create('ArrayIterator', array());
        
        $classUnderTest::setProcessor($dependency);
        $this->assertSame('XSLTProcessor', get_class($classUnderTest::getProcessor()));
        
        $classUnderTest::setProcessor($replacement);
        $this->assertSame('ArrayIterator', get_class($classUnderTest::getProcessor()));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Reset_dependencies($classUnderTest, $currentNamespace)
    {
        $dependency = function() use($classUnderTest) { return $classUnderTest::create('XSLTProcessor'); };
        $replacement = function() use($classUnderTest) { return $classUnderTest::create('ArrayIterator', array()); };
        
        $classUnderTest::setProcessor($dependency);
        $classUnderTest::resetDependencies();
        
        try {
            $classUnderTest::getProcessor();
        } catch (Select_MissingDependency_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Missing_dependency_causes_exception($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::getFoo();
        } catch (Select_MissingDependency_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Magic_setter_and_getter($classUnderTest, $currentNamespace)
    {
        $classUnderTest::defFoo('XSLTProcessor');
        $this->assertSame('XSLTProcessor', get_class($classUnderTest::getFoo()));
        
        $classUnderTest::setBar($classUnderTest::create('XSLTProcessor'));
        $this->assertSame('XSLTProcessor', get_class($classUnderTest::getBar()));
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Undefined_magic_call($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::fooBar();
        } catch (Select_UndefinedMethod_Exception $e) {
            $this->assertTrue(true);
            return;
        }
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Incomplete_magic_call($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::setSomething();
        } catch (Select_InvalidParameters_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Overloaded_magic_call($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::setSomething(1, 3, 5);
        } catch (Select_InvalidParameters_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Wrong_parameters_to_magic_call($classUnderTest, $currentNamespace)
    {
        try {
            $classUnderTest::setSomething(1);
        } catch (Select_InvalidParameters_Exception $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('The expected exception did not occur');
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Builder_provides_singleton_by_default($classUnderTest, $currentNamespace)
    {
        $classUnderTest::defNumbers('Numbers');
        
        // check that Numbers is not messing with this test
        $numbers1 = $classUnderTest::getNumbers();
        $this->assertNotSame('Complex', $numbers1->domain);
        $numbers1->domain = 'Complex';
        $this->assertSame('Complex', $numbers1->domain);
        
        // the test
        $numbers2 = $classUnderTest::getNumbers();
        $this->assertSame('Complex', $numbers2->domain);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Builder_provides_multiton_if_requested($classUnderTest, $currentNamespace)
    {
        $classUnderTest::defSomeDependency('Numbers', array(), false);
        
        // check that Numbers is not messing with this test
        $numbers1 = $classUnderTest::getSomeDependency();
        $this->assertNotSame('Complex', $numbers1->domain);
        $numbers1->domain = 'Complex';
        $this->assertSame('Complex', $numbers1->domain);
        
        // the test
        $numbers2 = $classUnderTest::getSomeDependency();
        $this->assertNotSame('Complex', $numbers2->domain);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Builder_can_define_and_instantiate_singleton_at_once($classUnderTest, $currentNamespace)
    {
        $classUnderTest::iniSeries('Numbers')->reset();
        $classUnderTest::getSeries()->setDomain('Complex');
        $this->assertSame('Complex', $classUnderTest::getSeries()->domain);
    }
    
    /**
     * @test
     * @dataProvider classUnderTest
     */
    public function Builder_can_define_and_instantiate_multiton_at_once($classUnderTest, $currentNamespace)
    {
        $classUnderTest::iniSeries('Numbers', array(), false)->reset();
        $classUnderTest::getSeries()->setDomain('Complex');
        $this->assertSame(null, $classUnderTest::getSeries()->domain);
    }
}
