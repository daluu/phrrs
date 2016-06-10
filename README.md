phrrs - PHp Robot Remote Server (can be pronounced "friss" or "furs"?)
=====

About this project
-----

PHP generic remote library server for Robot Framework. 

This project offers a generic remote server for Robot Framework, implemented in PHP, for use in creating remote libraries. It can alternatively be used for other purposes outside of Robot Framework. 

This remote server uses the PHP XML-RPC server from http://phpxmlrpc.sourceforge.net. Using composer will automatically install it as a dependency.

This remote server is stand-alone and is completely independent from Apache; it is run via PHP CLI.

Requirements
-----

You need PHP CLI to run PHP code from the command-line, and PHP composer to install the project.

To install PHP CLI under Ubuntu:
```
sudo apt-get install php5-cli
```

To install composer, follow the instructions given here: https://getcomposer.org/download/

Or, maybe more simple, directly download the `composer.phar` PHP executable and use it without any installation:
```
wget https://getcomposer.org/composer.phar
```

Installation
-----

Simply perform a composer install, it should take care of everything.

If you have composer installed:
```
composer install
```

Or if you have simply downloaded `composer.phar`:
```
php composer.phar install
```

Running the test suite
-----

A comprehensive test suite is provided with this project, using PhpUnit. There is even some tests that start in parallel the full remote server and Robot Framework tests with pybot to check the behavior from an end-to-end perspective. If you need to experiment I'd recommend playing around with the test suites.

Run the tests to check that nothing is broken and that the installation went fine:
```
vendor/phpunit/phpunit/phpunit tests/
```

Running the remote server
-----

The remote server is started using PHP CLI and runs completely indepently of Apache:

```
php src/BootstrapRobotRemoteServer.php <path-to-the-keywords-implementation-root-directory> <port-on-which-the-server-shall-listen-to>
```

You can define keyword implementations in as much files as you like. Constraints and allowances:
* Function names define the keyword names
* The functions/keywords must be in a class
* **Calls to these functions will be made statically**; `$this` will not be available
* Namespaces can be used
* Any number of classes can be used, several classes per file is OK, class names not matching the file names is OK
* Any number of files can be used, sub-folders can be used and will be recursively crawled
* These files will be required when any keyword from the file is requested for the first time; if none of its keyword is ever used, the file won't be required

About the Robot Framework remote protocol itself:
* `stop_remote_server` is implemented and available as a keyword
* Robot Framework lists are available and will be translated to PHP sequential arrays
* PHP sequential arrays will be translated back to Robot Framework lists; **the empty array will be considered as a sequential array/list**
* Robot Framework dictionaries are available and will be translated to PHP associative arrays
* PHP associative arrays will be translated back to Robot Framework dictionaries
* PHP objects will also be transated back to Robot Framework dictionaries, as per `get_object_vars`
* Nesting and mixing Robot Framework lists and dictionaries/PHP sequential arrays, associative arrays and objects are supported

Verbosity:
* By default, the remote server will print out useful info about files crawled to find keyword implementations as well as the resulting set of found keyword implementations
* The server will also print on which port it listens to
* The goal is to have some useful information to quickly debug test suites, for instance when the tests are run by a CI server
* Warnings are also printed out when encountering dubious cases, for instance when the keyword implementation is sending back to Robot Framework a PHP value of type `unknown` or `resource`
* However, if needed, the option `--quiet` or `-q` can be added to remove this verbosity completely
* In any case, if something goes really bad (think internal/unexpected error in the remote server code itself) a big error log will be printed with the complete stack trace in order to be able to debug it properly

Logging in the keyword implementations:
* **You can echo and print whatever you want in keyword implementations, it will be captured and forwarded back to the Robot Framework log files** (typically into `log.html`)
* Useful to add information that will make debug easy when anything goes wrong, or simply to understand how things work -- try out some `var_dump` and you'll see!

Extra! if you want to play around with the XML-RPC protocol, you can run some 'demo' instance of the remote server code that will print out what is received and sent back by the server: (just modify the file to add more XML messages)
```
php src/DemoRobotRemoteServer.php <path-to-the-keywords-implementation-root-directory>
```

Need help?
-----

For inquiries:

You can post inquiries to Robot Framework Users Google Group as I am a member of that group and periodically check it. If there is enough inquiry activity, I may start a Google Group, etc. for it. You may also file GitHub issues to the project for me to look into as well.
