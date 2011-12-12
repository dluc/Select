# API Documentation

## Select::setNamespace(string $namespace)

Set the default namespace for classes, a prefix string used to build class names.

**Notes**

Both "old style" and proper namespaces are supported, e.g.

        Select::setNamespace('MyApp_')
        Select::setNamespace('MyApp\\')

If a class does not exist in the namespace, the search occurs in the global space, e.g.

        Select::setNamespace('MyApp_')
        Select::getClass('Foo')             // returns 'MyApp_Foo'
        Select::getClass('XSLTProcessor')   // returns 'XSLTProcessor'
        
        

## Select::resetNamespace()

Reset the default namespace as defined in the class.

**Notes**

The default namespace is empty but can be overridden extending the base class, e.g.

        namespace MyApp;
        class DI extends \Select {
            const DEFAULT_NAMESPACE = 'MyApp\\';
            static protected $_namespace = self::DEFAULT_NAMESPACE;
        }
        
        
## Select::replaceClass(string $oldClass, string $newClass)

Replace a class so that new objects and static calls will use the new class.
    
**Notes**
    
The old class name must be passed without namespace, e.g.
        
        Select::replaceClass('Mailer', 'FakeMailer')                // correct
        
        Select::replaceClass('Mailer', 'Mocks\Mailer')              // correct
        
        Select::setNamespace('')
        Select::replaceClass('MyApp\Mailer', 'Mocks\Mailer')        // correct
        
        Select::setNamespace('MyApp\\')
        Select::replaceClass('MyApp\Mailer', 'Mocks\Mailer')        // wrong
        
        
## Select::getClass(string $class)

Return a class name.
    
* If there is a class in the defined namespace this is returned,
* otherwise if there is a class in the global space, this is returned,
* otherwise returns the class in the defined namespace.

Examples
    
        Select::setNamespace('MyApp_')
        Select::getClass('Foo')
            * 'MyApp_Foo' does not exist
            * 'Foo' does not exist
            * return 'MyApp_Foo'
    
        Select::setNamespace('MyApp_')
        Select::getClass('XSLTProcessor')
            * 'MyApp_XSLTProcessor' does not exist
            * 'XSLTProcessor' exists
            * return 'XSLTProcessor'
    
        Select::setNamespace('MyApp_')
        // ... define a class MyApp_XSLTProcessor in the application ...
        Select::getClass('XSLTProcessor')
            * 'MyApp_XSLTProcessor' exists
            * return 'MyApp_XSLTProcessor'
        
        
## Select::resetClass(string $class)
        
Remove replacements for the specified class.
    
**Notes**
        
The class name must be passed without namespace
        
**Examples**

        Select::setNamespace('MyApp_')
        Select::getClass('XSLTProcessor')                           // returns 'XSLTProcessor'
        Select::replaceClass('XSLTProcessor', 'MyApp_XSLTProcessor') 
        Select::getClass('XSLTProcessor')                           // returns 'MyApp_XSLTProcessor'
        Select::resetClass('XSLTProcessor')
        Select::getClass('XSLTProcessor')                           // returns 'XSLTProcessor'
        
        
## Select::resetClasses()
        
Remove all defined replacements, restore default behaviour.
        
        
## Select::create(string $class)
        
Return a new object of the specified class.
    
**Notes**

The class name must be passed without namespace
        
## Select::get&lt;Dependency name>()
    
Get a defined dependency.  If the dependency is not defined throws and exception.
If the dependency is explicitly defined as a multiton it always returns a new object.
        
## Select::set&lt;Dependency name>(object $object)
    
Define and store the passed object as a new dependency.  Overwrite any existing
dependency with the same name.  The dependency is considered to be a singleton.
        
## Select::def&lt;Dependency name>(string $class, array $contructorParameters, boolean $isSingleton)
        
Define a new dependency, without instantiating the object.  Overwrite any existing
dependency with the same name.  Constructor parameters can be passed after the class name.
By default the dependency is defined to be a singleton, this can be overridden with the
third parameter.

**Examples**
    
        Select::setMailer('Mailer')
        Select::setMailer('Mailer', array('smtp.gmail.com', 465))
        Select::setMailer('Mailer', array('smtp.gmail.com', 465), false)
        

## Select::ini&lt;Dependency name>()

Define a new dependency, instantiating and returning the object.  Overwrite any existing
dependency with the same name.  Constructor parameters can be passed after the class name.
By default the dependency is defined to be a singleton, this can be overridden with the
third parameter.
    
**Examples**
    
        Select::iniMailer('Mailer')->setSender('root@localhost')
        Select::iniMailer('Mailer', array('smtp.gmail.com', 465))->setSender('root@localhost')
        Select::iniMailer('Mailer', array('smtp.gmail.com', 465), false)->setSender('root@localhost')
        
