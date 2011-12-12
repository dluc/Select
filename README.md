# Description

**Select** is a static **Service Locator** implementation with PHP magic 
methods. It allows to **replace classes** and can be used to **hold 
components/services**, identified by unique names and automatically exposed
with getter methods.

_Select_ is designed to be subclassed with a custom class name, as opposed to 
the common injection through constructors.  To replace _Select_ you subclass 
the main class, implementing the same interface _iSelect_.  For instance: 
during tests you can either use a different set of definitions (suggested) or
use a mocked Service Locator class.

_Service Locator_ is an _Inversion of Control_ pattern, an alternative to 
_Constructor Injection_ and _Setter Injection_.  Each approach has pros and
cons, you might want to read 
[Inversion of Control Containers and the Dependency Injection pattern](http://martinfowler.com/articles/injection.html "Inversion of Control Containers and the Dependency Injection pattern")
for more details on IoC, Dependency Injection and Service Locators (in the Java
world).

_Select_ does not require any configuration, it is simple to introduce the
feature if required, see `Select::def<*>` API for details.




# How to start

To start using _Select_ you should extend the original class and set your 
namespace (optional), choosing a class name such as _SL_, _IOC_, _Manager_ etc.
In order to do this create a file, ie _DI.php_ with this code:

        namespace MyApp;
        class DI extends \Select {
            const DEFAULT_NAMESPACE = 'MyApp\\';
            static protected $_namespace = self::DEFAULT_NAMESPACE;
        }
    
or
    
        class DI extends Select {
            const DEFAULT_NAMESPACE = 'MyApp_';
            static protected $_namespace = self::DEFAULT_NAMESPACE;
        }

You can also configure the included unit test case (see _data provider_ and
_setUp_) with the custom class name, to verify that everything works properly.
You might have to create a fixture, see _tests/fixtures/select\_*NS.php_.

Notice that subclasses don't need to explicit any interface because _Select_
uses magic methods to catch calls to the stored dependencies.  _iSelect_ should
be used when replacing _Select_ with a new or fake implementation.




# Customising the class methods

If you intend to override methods please note that _Select_ uses 
`func_get_args()` to fetch parameters not explicitly defined in function
signatures.  

To add new methods or rename existing methods, please note that
_Select_ uses `__callStatic` to catch `def/ini/set/get<*>` calls. See
[`__callStatic` documentation](http://php.net/manual/en/language.oop5.overloading.php "PHP manual") for details.




# Inversion of control

The main idea is to separate configuration (class names) from implementation
(class instantiations and static calls), avoiding hard coded class names, so
that they can be replaced by third parties and during tests.  _Select_ is an
implementation of a Service Locator, a Registry holding classes and services
definitions.

The first step should be replacing class symbols with class identifiers:

        $mailer = new Mailer()
    
with:
    
        $mailer = Select::create('Mailer')

This allows to change the actual class used for the identifier 'Mailer'. 

You might also want to use _Select_ as a Service Locator, storing dependencies
definitions in the container and retrieving them whenever you want. 
The previous code becomes:

        Select::defMailer('Mailer')
        $mailer = Select::getMailer()

To keep definitions and uses separated, all the definitions can be stored in a
"configuration file", i.e. _definitions.php_, e.g.

        Select::defMailer('Mailer')
        Select::defCache('MemCache')
        Select::defHTTPClient('HTTPClientV2', array('proxy.mydomain.net:80'))
        Select::defAuthService('AuthService', array('192.168.0.1', 636))
        etc.

Dependencies defined with `Select::def<*>` will not instantiate until they are
requested for the first time.  However you can also define and instantiate at
once if you like:

        $mailer = Select::iniMailer('Mailer')
    
and
    
        Select::iniDB('DBConnector', array('127.0.0.1', 3306, 'user', 'password'))->connect()
        // ...
        Select::getDB()->disconnect()
    
And you can also postpone a dependency definition after its creation:

        $logger = Select::create('Logger', '/tmp/app.log')
        $logger->rotate()
        Select::setLogger($logger)




# Constructors, Singletons, Multitons

With regards to the constructor parameters, they can be passed to 
`Select::def<*>` and to `Select::ini<*>` as an array, after the class name.
`Select::create` expects constructor's parameters inline after the class name
instead.  See the API documentation for details and examples. 

By default all dependencies defined and/or created with
`Select::<def|ini|set><*>` are considered singletons.  See the API
documentation for multitons.  
`Select::create` always returns a new object.

Constructors are limited to a maximum of 6 parameters, if you need more you
shall override `Select::_create`, or perhaps reduce your code complexity ;-)
    

# Notes

_Select_ exceptions classes are hard coded, there shouldn't be any reason to 
replace them. Each error type has a dedicated exception class subclassing
`Select_Exception`.

Static calls require two lines of code, one to fetch the class name and one
for the actual code:

        $class = Select::getClass('AStaticClass')
        $class::someMethod()

The cost of the abstraction should be in the order of few ms every 1000
instantiations (tested on low spec hw).  Have a look at _tests/speed.php_ to
test _Select_ on your system.  _Select_ requires PHP 5.3+.
