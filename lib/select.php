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


/**
 * Service locator interface
 */
require dirname(__FILE__) . '/iselect.php';

/**
 * Service locator exceptions
 */
class Select_Exception extends \Exception {}
class Select_InvalidClass_Exception      extends Select_Exception { }
class Select_TooManyParameters_Exception extends Select_Exception { }
class Select_MissingDependency_Exception extends Select_Exception { }
class Select_UndefinedMethod_Exception   extends Select_Exception { }
class Select_InvalidParameters_Exception extends Select_Exception { }

/**
 * Service locator container
 *
 * Allows to inject classes and components/services at run time to replace
 * behaviour and mock dependencies
 *
 * How-to extend the class with a custom namespace:
 *
 *         class My_DI extends JeDI {
 *             const DEFAULT_NAMESPACE = 'My_';
 *             static protected $_namespace = self::DEFAULT_NAMESPACE;
 *         }
 *
 *         class My_Mailer {
 *         }
 *
 *         My_DI::getClass('Mailer') returns 'My_Mailer'
 */
class Select implements iSelect {
    
    /**
     * @var string Default NS (copy and customise to extend the class)
     * @wtf http://j.mp/paamayim-nekudotayim
     */
    const DEFAULT_NAMESPACE = '';
    
    /**
     * @var string Class name space (copy without change to extend the class)
     */
    static protected $_namespace = self::DEFAULT_NAMESPACE;
    
    /**
     * @var array Map name:class Class names replacements
     */
    static protected $_classes = array();
    
    /**
     * @var array Map name:object Dependent-on oObjects
     */
    static protected $_dependencies = array();
    
    /**
     * @var array Map name:object Singletons container
     */
    static protected $_singletons = array();
    
    static protected $_cachedClassLookups = array();
    
    /**
     * Set a new class name space
     *
     * @param string $namespace Namespace
     */
    static public function setNamespace($namespace)
    {
        static::$_namespace = $namespace;
        static::resetCache();
    }
    
    /**
     * Restore the initial class name space
     */
    static public function resetNamespace()
    {
        static::$_namespace = static::DEFAULT_NAMESPACE;
        static::resetCache();
    }
    
    /**
     * Replace an original class with a new one
     *
     * @param string $classNoNS   Original name, withouth namespace,
     *                            ie "Mail_Transport_Uploader"
     *                            instead of "Zend_Mail_Transport_Uploader"
     * @param string $replacement New class name, complete of namespace,
     *                            ie "SomeApp_SomeClass"
     */
    static public function replaceClass($classNoNS, $replacement)
    {
        static::$_classes[$classNoNS] = $replacement;
        static::$_cachedClassLookups[$classNoNS] = $replacement;
    }
    
    /**
     * Remove cached lookups
     */
    static public function resetCache()
    {
        static::$_cachedClassLookups = array();
    }
    
    /**
     * Get class name
     *
     * @param string $classNoNS Original name, without namespace
     *
     * @return string class
     */
    static public function getClass($classNoNS)
    {
        return static::_getClass($classNoNS, false);
    }
    
    /**
     * Restore original name for a class
     *
     * @param string $classNoNS Original name, without namespace
     */
    static public function resetClass($classNoNS)
    {
        unset(static::$_classes[$classNoNS]);
        if (isset(static::$_cachedClassLookups[$classNoNS])) unset(static::$_cachedClassLookups[$classNoNS]);
    }
    
    /**
     * Remove all class replacements
     */
    static public function resetClasses()
    {
        static::$_classes = array();
        static::resetCache();
    }
    
    /**
     * Remove all stored dependencies
     */
    static public function resetDependencies()
    {
        static::$_dependencies = array();
        static::$_singletons = array();
    }
    
    /**
     * Restore the class to initial status
     */
    static public function reset()
    {
        static::resetNamespace();
        static::resetClasses();
        static::resetDependencies();
    }
    
    /**
     * Return a new object instance of the class
     *
     * @param string $classNoNS Name of the class, without namespace
     *
     * @throws Select_TooManyParameters_Exception
     *
     * @return <$classNoNS> New instance
     */
    static public function create($classNoNS)
    {
        $parameters = func_get_args();
        array_shift($parameters);
        return static::_create($classNoNS, $parameters);
    }
    
    /**
     * Magic getter/setter, allows getMailer/setMailer/setLogger/getLogger etc.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws Select_InvalidParameters_Exception
     * @throws Select_UndefinedMethod_Exception
     *
     * @return mixed
     */
    static public function __callStatic($methodname, $arguments)
    {
        $prefix = substr($methodname, 0, 3);
        $name = strtoupper(substr($methodname, 3));
        
        switch ($prefix) {
            case 'def':
                $count = count($arguments);
                switch ($count) {
                    case 1: return static::_defineDependency($name, $arguments[0]);
                    case 2: return static::_defineDependency($name, $arguments[0], $arguments[1]);
                    case 3: return static::_defineDependency($name, $arguments[0], $arguments[1], $arguments[2]);
                }
                throw new Select_InvalidParameters_Exception("Invalid call to `$name`");
            case 'ini':
                $count = count($arguments);
                switch ($count) {
                    case 1: static::_defineDependency($name, $arguments[0]); break;
                    case 2: static::_defineDependency($name, $arguments[0], $arguments[1]); break;
                    case 3: static::_defineDependency($name, $arguments[0], $arguments[1], $arguments[2]); break;
                    default: throw new Select_InvalidParameters_Exception("Invalid call to `$name`");
                }
                return static::_getDependency($name);
            case 'set':
                if (count($arguments) != 1) throw new Select_InvalidParameters_Exception("Invalid call to `$name`");
                
                return static::_storeDependency($name, $arguments[0]);
            case 'get':
                return static::_getDependency($name);
        }
        
        throw new Select_UndefinedMethod_Exception("Call to undefined method `$methodname`");
    }
    
    // ****************************************************************************************************
    
    /**
     * Return a new object instance of the class
     *
     * @param string $classNoNS Name of the class, without namespace
     * @param array $p
     *
     * @throws Select_TooManyParameters_Exception
     *
     * @return <$classNoNS> New instance
     */
    static protected function _create($classNoNS, $p)
    {
        $class = static::_getClass($classNoNS);
        $count = count($p);
        
        switch ($count) {
            case  0: return new $class();
            case  1: return new $class($p[0]);
            case  2: return new $class($p[0], $p[1]);
            case  3: return new $class($p[0], $p[1], $p[2]);
            case  4: return new $class($p[0], $p[1], $p[2], $p[3]);
            case  5: return new $class($p[0], $p[1], $p[2], $p[3], $p[4]);
            case  6: return new $class($p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
        }
        
        throw new Select_TooManyParameters_Exception('Too many constructor parameters');
    }
    
    /**
     * Store dependency definition with constructor parameters
     *
     * @param string  $name
     * @param string  $class
     * @param array   $parameters
     * @param boolean $isSingleton
     */
    static protected function _defineDependency($name, $class, $parameters = array(), $isSingleton = true)
    {
        static::$_dependencies[$name] = array('class' => $class, 'parameters' => $parameters, 'isSingleton' => $isSingleton);
        if (isset(static::$_singletons[$name])) unset(static::$_singletons[$name]);
    }
    
    /**
     * Store component/service dependency
     *
     * @param string $name
     * @param object $object
     */
    static protected function _storeDependency($name, $object)
    {
        if (!is_object($object)) throw new Select_InvalidParameters_Exception('Dependency must be an object');
        
        static::$_dependencies[$name]['class'] = null;
        static::$_dependencies[$name]['parameters'] = null;
        static::$_dependencies[$name]['isSingleton'] = true;
        static::$_singletons[$name] = $object;
    }
    
    /**
     * Return a component/service stored in the class
     *
     * @param string $name
     *
     * @throws Select_MissingDependency_Exception
     *
     * @return object
     */
    static protected function _getDependency($name)
    {
        if (!isset(static::$_dependencies[$name])) throw new Select_MissingDependency_Exception("Dependency `$name` not found");
        
        if (static::$_dependencies[$name]['isSingleton']) {
            if (!isset(static::$_singletons[$name])) {
                $class = static::getClass(static::$_dependencies[$name]['class']);
                $parameters = static::$_dependencies[$name]['parameters'];
                static::$_singletons[$name] = static::_create($class, $parameters);
            }
            return static::$_singletons[$name];
        }
        
        $class = static::getClass(static::$_dependencies[$name]['class']);
        $parameters = static::$_dependencies[$name]['parameters'];
        return static::_create($class, $parameters);
    }
    
    /**
     * Return full class name
     *
     * @param string $classNoNS Name of the class, without namespace
     *
     * @throws Select_InvalidClass_Exception
     *
     * @return string Class name
     */
    static protected function _getClass($classNoNS, $checkIfExists = true)
    {
        if (isset(static::$_classes[$classNoNS])) {
            $class = static::$_classes[$classNoNS];
        } else {
            if (isset(static::$_cachedClassLookups[$classNoNS])) {
                $class =static::$_cachedClassLookups[$classNoNS];
            } else {
                $classWNS = static::$_namespace . $classNoNS;
                if (class_exists($classWNS)) {
                    $class = $classWNS;
                } elseif (class_exists($classNoNS)) {
                    $class = $classNoNS;
                } elseif (!$checkIfExists) {
                    $class = $classWNS;
                } else {
                    $class = $classNoNS;
                }
                // @note speed up successive searches ~10%
                static::$_cachedClassLookups[$classNoNS] = $class;
            }
        }
        
        if ($checkIfExists && !class_exists($class)) {
            $classWNS = isset($classWNS) ? $classWNS : static::$_namespace . $classNoNS ;
            throw new Select_InvalidClass_Exception("Unable to find class `$class` [`$classWNS`]");
        }
        
        return $class;
    }
}