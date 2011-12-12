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
interface iSelect
{
    
    /**
     * Set a new class name space
     *
     * @param string $namespace Namespace
     */
    static public function setNamespace($namespace);
    
    /**
     * Restore the initial class name space
     */
    static public function resetNamespace();
    
    /**
     * Replace an original class with a new one
     *
     * @param string $classNoNS   Original name, withouth namespace,
     *                            ie "Mail_Transport_Uploader"
     *                            instead of "Zend_Mail_Transport_Uploader"
     * @param string $replacement New class name, complete of namespace,
     *                            ie "SomeApp_SomeClass"
     */
    static public function replaceClass($classNoNS, $replacement);
    
    /**
     * Get class name
     *
     * @param string $classNoNS Original name, without namespace
     *
     * @return string class
     */
    static public function getClass($classNoNS);
    
    /**
     * Restore original name for a class
     *
     * @param string $classNoNS Original name, without namespace
     */
    static public function resetClass($classNoNS);
    
    /**
     * Remove all class replacements
     */
    static public function resetClasses();
    
    /**
     * Remove all stored dependencies
     */
    static public function resetDependencies();
    
    /**
     * Restore the class to initial status
     */
    static public function reset();
    
    /**
     * Return a new object instance of the class
     *
     * @param string $classNoNS Name of the class, without namespace
     *
     * @return <$classNoNS> New instance
     */
    static public function create($classNoNS);
    
    /**
     * Magic getter/setter, allows getMailer/setMailer/setLogger/getLogger etc.
     *
     * Should respond to:
     * 		def<Dependency name>
     * 		ini<Dependency name>
     * 		set<Dependency name>
     * 		get<Dependency name>
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    static public function __callStatic($methodname, $arguments);
}