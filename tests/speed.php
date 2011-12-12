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


error_reporting(-1);
$runs = 100000;

require dirname(__FILE__) . '/../lib/select.php';

class Timer {
    protected $last, $timers = array(), $length = 10;
    
    public function start() {
        $this->last = microtime(true);
    }
    public function mark($name) {
        $this->timers[$name] = microtime(true) - $this->last;
        $this->last = microtime(true);
        $this->length = max(strlen($name) + 2, $this->length);
    }
    public function display($runs) {
        printf("%-{$this->length}s: %s" . PHP_EOL, 'Iterations', number_format($runs));
        
        foreach ($this->timers as $name => $value) printf("%-{$this->length}s: %.3f secs" . PHP_EOL, $name, $value);
    }
}

class Foo {}
class Bar {}
class Baz {}
class Qux {}
class Singleton {
    static $me = null;
    static public function getInstance() {
        if (is_null(static::$me)) {
            $class = __CLASS__;
            static::$me = new $class;
        }
        return static::$me;
    }
}

$timer = new Timer;
$timer->start();

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { new Foo(1); }
$timer->mark('Creation: PHP default');

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { Select::create('Bar', 1); }
$timer->mark('Creation: Select::create');

// *******************************************************************
Select::replaceClass('Baz', 'Qux');
for ($i = 0; $i < $runs; $i++) { Select::create('Baz', 1); }
$timer->mark('Creation: Select::create with replace');

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { Singleton::getInstance(); }
$timer->mark('Singleton: classic');

// *******************************************************************
Select::defDependency('Baz');
for ($i = 0; $i < $runs; $i++) { Select::getDependency(); }
$timer->mark('Singleton: Select::getDependency');

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { Select::getClass('XSLTProcessor'); }
$timer->mark('Lookup: core class');

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { Select::getClass('FooBar'); }
$timer->mark('Lookup: no replacement no class');

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { Select::getClass('Foo'); }
$timer->mark('Lookup: existing with no replacement');

// *******************************************************************
Select::replaceClass('Baz', 'Qux');
for ($i = 0; $i < $runs; $i++) { Select::getClass('Baz'); }
$timer->mark('Lookup: replaced class');

// *******************************************************************
for ($i = 0; $i < $runs; $i++) { Select::defSomething('Baz'); }
$timer->mark('Config: define a dependency');


$timer->display($runs);
