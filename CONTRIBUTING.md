## Filing bug reports ##

Bugs or feature requests can be posted online on the [GitHub issues](http://github.com/nrk/predis/issues)
section of the project.

When reporting bugs, in addition to the obvious description of your issue you __must__ always provide
some essential information about your environment such as:

  1. version of Predis (check the `VERSION` file or the `Predis\Client::VERSION` constant).
  2. version of Redis (check the `redis_version` field returned by [`INFO`](http://redis.io/commands/info)).
  3. version of PHP.
  4. name and version of the operating system.
  5. when possible, a small snippet of code that reproduces the issue.

__Think about it__: we do not have a crystal ball and cannot predict things and peer into the unknown,
so please provide as much details as possible to help us isolating issues and fix them.

__Never__ use GitHub issues to post generic questions about Predis! When you have questions about
how Predis works or how it can be used, please just hop me an email and I will get back to you as
soon as possible.


## Contributing code ##

If you want to work on Predis, it is highly recommended that you first run the test suite in order to
check that everything is OK, and report strange behaviours or bugs. When modifying Predis please make
sure that no warnings or notices are emitted by PHP by running the interpreter in your development
environment with the `error_reporting` variable set to `E_ALL | E_STRICT`.

The recommended way to contribute to Predis is to fork the project on GitHub, create new topic branches
on your newly created repository to fix or add features (possibly with tests covering your modifications)
and then open a new pull request with a description of the applied changes. Obviously you can use any
other Git hosting provider of your preference.

When writing code please follow the [basic coding (PSR-1)](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md)
and [coding style (PSR-2)](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)
standards and stick with the conventions used in Predis to name classes and interfaces.

Please also follow some basic [commit guidelines](http://git-scm.com/book/ch5-2.html#Commit-Guidelines)
before opening pull requests.
