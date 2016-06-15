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

Requirements to run the test suite
-----

Finally, the test suite contains some end-to-end tests that uses `pybot`, Robot Framework's test case runner. So you'll need `pybot` installed and available in the PATH for these tests to run. This may sound like an obvious requirement since you are certainly looking for PHRRS in order to run Robot Framework test cases, but maybe you will use `jybot` instead of `pybot`, or maybe your `pybot` executable will not be globally installed and available in the PATH. You can check that `pybot` is available with the following command

```
pybot --version
```


Installation and running as a composer dependency
=====

You can use composer to get this project as a dependency which will install into the `vendor` directory.
This is the way to go if you are already using composer and that your goal is to actually use the Robot Framework remote server, not to debug it or to play around with the protocol.

Installation
-----

Add the project as a dependency to your composer project and then perform the usual composer install.

If you have composer installed:

```
composer require jplambert/phrrs
composer install
```

Or if you have simply downloaded `composer.phar`:

```
php composer.phar require jplambert/phrrs
php composer.phar install
```

Running the remote server
-----

The remote server start command is accessible from the handy `vendor/bin` directory:

```
vendor/bin/php-robot-remote-server <path-to-the-keywords-implementation-root-directory> <port-on-which-the-server-shall-listen-to>
```

Until you find some bug into the remote server, this should be all you need to know! :-)


Installation and running as a stand-alone project
=====

You can also use the project by itself. Especially useful if you want to debug it, run its test suite, or play around with the Robot Framework remote and XML-RPC protocols.

Installation
-----

Retrieve the project from GitHub and then simply perform a composer install. That should take care of everything.

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

The remote server is started using PHP CLI and runs indepently of Apache:

```
php src/StartRobotRemoteServer.php <path-to-the-keywords-implementation-root-directory> <port-on-which-the-server-shall-listen-to>
```

Experimenting with the protocol
-----

Extra! If you want to play around with the XML-RPC protocol, you can run some 'demo' instance of the remote server code that will print out what is received and sent back by the server: (just modify the content of `DemoRobotRemoteServer.php` to add more XML messages)

```
php src/DemoRobotRemoteServer.php <path-to-the-keywords-implementation-root-directory>
```


Things to know about this implementation of the Robot Framework remote protocol
=====

Keyword definition and execution
-----

You can define keyword implementations in as much files as you like. Rules:
* Function names define the keyword names
* The functions/keywords must be in classses
* Any number of classes can be used, several classes per file is OK, class names not matching the file names is OK
* Any number of files can be used, sub-folders can be used and will be crawled recursively
* Namespaces can be used
* If the same keyword is declared twice (i.e. two functions with the same name in different classes/files) then only one of them will be taken into account, and a warning will be issued

About the execution:
* Each given file will be required when any keyword from the file is requested for the first time; if none of its keyword is ever used, the file won't be required
* **Calls to these functions will be made statically**; `$this` will not be available

Stopping the server programmatically
-----

`stop_remote_server` is implemented and available as a keyword.

Data: Robot Framework vs. PHP
-----

* Robot Framework lists are available and will be translated to PHP sequential arrays
* PHP sequential arrays will be translated back to Robot Framework lists; **the empty PHP array will be considered as a sequential array and thus will be translated as a Robot Framework list**
* Robot Framework dictionaries are available and will be translated to PHP associative arrays
* PHP associative arrays will be translated back to Robot Framework dictionaries
* PHP objects will also be transated back to Robot Framework dictionaries, as per `get_object_vars`
* Nesting and mixing Robot Framework lists and dictionaries/PHP sequential arrays, associative arrays and objects are supported

Verbosity
-----

* By default, the remote server will print out useful info about files crawled to find keyword implementations as well as the resulting set of found keyword implementations
* The server will also print on which port it listens to
* The goal is to have some useful information to quickly debug test suites, for instance when the tests are run by a CI server
* Warnings are also printed out when encountering dubious cases, for instance when the keyword implementation is sending back to Robot Framework a PHP value of type `unknown` or `resource`
* However, if needed, the option `--quiet` or `-q` can be added to remove this verbosity completely
* In any case, if something goes really bad (think internal/unexpected error in the remote server code itself) a big error log will be printed with the complete stack trace in order to be able to debug it properly

Logging in the keyword implementations
-----

* **You can echo and print whatever you want in keyword implementations, it will be captured and forwarded back to the Robot Framework log files** (typically into `log.html`)
* Useful to add information that will make debug easy when anything goes wrong, or simply to understand how things work -- try out some `var_dump` and you'll see!


Need help?
=====

You can post inquiries to Robot Framework Users Google Group as I am a member of that group and periodically check it. If there is enough inquiry activity, I may start a Google Group, etc. for it. You may also file GitHub issues to the project for me to look into as well.
