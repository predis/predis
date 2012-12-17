The `Predis\\Client` class
--------------------------

.. php:namespace:: Predis

The `Client` class is the main class users interact with in Predis. The first
thing you'll do to start communicating with Redis is instantiate a `Client`.

Constructing a Client
=====================

The `Client` constructor takes two arguments. The first (``$parameters``) is a
set of information about the Redis connection you'd like to make. The second
(``$options``) is a set of options to configure the client.

.. php:class:: Client

   .. php:method:: __construct($parameters = null, $options = null)

      Creates a new `Client` instance.

      :param $parameters: Connection parameters
      :param $options:    Client options


Connection Parameters
'''''''''''''''''''''

These parameters are used to control the behaviour of the underlying connection
to Redis. They can be specified using a URI string::

   $client = new Predis\Client('tcp://127.0.0.1:6379?database=2')

Or, using an associative array::

   $client = new Predis\Client(array(
       'scheme'   => 'tcp',
       'host'     => '127.0.0.1',
       'port'     => 6379,
       'database' => 3
   ));

By default, Predis supports a lengthy list of connection parameters.

.. note::

   Since the client can use different :doc:`connection backends`, actual support
   for certain parameters depends on the value of ``scheme`` and the connection
   backends registered using the ``connections`` client option.

==================  =============================================  =========  =========================================
parameter           description                                    default    supported by
==================  =============================================  =========  =========================================
scheme              Instructs the client how to connect to Redis.  tcp        `Predis\\Connection\\StreamConnection`
                    Supported values are: ``tcp``, ``unix``,                  `Predis\\Connection\\PhpiredisConnection`
                    ``http``. Certain values such as ``http``                 `Predis\\Connection\\WebdisConnection`
                    change the underlying connection backend.
------------------  ---------------------------------------------  ---------  -----------------------------------------
host                IP address or hostname of the server.          127.0.0.1  `Predis\\Connection\\StreamConnection`
                    Ignored when using the ``unix`` scheme.                   `Predis\\Connection\\PhpiredisConnection`
                                                                              `Predis\\Connection\\WebdisConnection`
------------------  ---------------------------------------------  ---------  -----------------------------------------
port                TCP port the server listens on.                6379       `Predis\\Connection\\StreamConnection`
                    Ignored when using the ``unix`` scheme.                   `Predis\\Connection\\PhpiredisConnection`
                                                                              `Predis\\Connection\\WebdisConnection`
------------------  ---------------------------------------------  ---------  -----------------------------------------
path                Path of the UNIX domain socket the server is   not set    `Predis\\Connection\\StreamConnection`
                    listening on, used only in combination with               `Predis\\Connection\\PhpiredisConnection`
                    the ``unix`` scheme.
                    Example: ``/tmp/redis.sock``.
------------------  ---------------------------------------------  ---------  -----------------------------------------
database            Redis database to select when connecting.      not set    `Predis\\Connection\\StreamConnection`
                    Its effect is the same of using `SELECT`_.                `Predis\\Connection\\PhpiredisConnection`
------------------  ---------------------------------------------  ---------  -----------------------------------------
timeout             Timeout to perform the connection to Redis.    5.0        `Predis\\Connection\\StreamConnection`
                    Its value is expressed in seconds as a float              `Predis\\Connection\\PhpiredisConnection`
                    allowing sub-second resolution.                           `Predis\\Connection\\WebdisConnection`
------------------  ---------------------------------------------  ---------  -----------------------------------------
read_write_timeout  Timeout for read and write operations.         platform   `Predis\\Connection\\StreamConnection`
                    Its value is expressed in seconds as a float   dependent  `Predis\\Connection\\PhpiredisConnection`
                    allowing sub-second resolution.
------------------  ---------------------------------------------  ---------  -----------------------------------------
async_connect       Tells the client to perform the connection     not set    `Predis\\Connection\\StreamConnection`
                    asynchronously without waiting for it to be    (false)
                    fully estabilished.
------------------  ---------------------------------------------  ---------  -----------------------------------------
persistent          The underlying socket is left intact after a   not set    `Predis\\Connection\\StreamConnection`
                    GC collection or when the script terminates    (false)
                    (only when using FastCGI or php-fpm).
------------------  ---------------------------------------------  ---------  -----------------------------------------
iterable_multibulk  `Multi-bulk replies`_ are returned as PHP      false      `Predis\\Connection\\StreamConnection`
                    iterable objects, making them streamable.
------------------  ---------------------------------------------  ---------  -----------------------------------------
alias               String used to identify a connection by name.  not set    Backend independent.
                    This is useful with :doc:`clustering` and
                    :doc:`replication`.
------------------  ---------------------------------------------  ---------  -----------------------------------------
weight              This is only used with :doc:`clustering` and   not set    Backend independent.
                    determines the proportion of the load the
                    corresponding server will bear relative to
                    other nodes in the cluster.
------------------  ---------------------------------------------  ---------  -----------------------------------------
user                Username for HTTP authentication (`Webdis`_).  not set    `Predis\\Connection\\WebdisConnection`
------------------  ---------------------------------------------  ---------  -----------------------------------------
pass                Password for HTTP authentication (`Webdis`_).  not set    `Predis\\Connection\\WebdisConnection`
==================  =============================================  =========  =========================================

.. _SELECT: http://redis.io/commands/select
.. _Multi-bulk replies: http://redis.io/topics/protocol#multi-bulk-reply
.. _Webdis: http://webd.is/

Users can also specify their own parameters, they will simply be ignored by the
client but can be used later to pass additional information for custom purposes.


Client Options
''''''''''''''

Several behaviours of `Client` can be controlled via client options with values
that vary depending on the nature of each option: some of them accept primitive
types while others can also take instances of classes implementing some specific
interfaces defined by Predis, which can be useful to completely override the
standard ones used by `Client`::

   $client = new Predis\Client($parameters, array(
       'prefix'      => 'predis:'
       'profile'     => '2.6',
       'connections' => array(
           'tcp'  => 'Predis\Connection\PhpiredisConnection',
           'unix' => 'Predis\Connection\PhpiredisConnection',
       ),
   ));

To achieve an even higher level of customizability, certain options also accept
callables acting as initializers that can be leveraged to gain full control over
the initialization of option values (e.g. instances of classes) before returning
them to `Client`::

   $client = new Predis\Client('tcp://127.0.0.1', array(
       'prefix'  => 'predis:',
       'profile' => function ($options, $option) {
           // Callable initializers have access to the whole set of options
           // (1st argument) and to the current option instance (2nd argument).

           return new Predis\Profile\ServerVersion26();
       },
   ));

Users can also specify their own custom options to pass additional information.
Just like standard options, they are accessible from callable initializers::

   $client = new Predis\Client('tcp://127.0.0.1', array(
        // 'commands' is a custom option, actually unknown to Predis.
       'commands' => array(
           'set' => Predis\Command\StringSet,
           'get' => Predis\Command\StringGet,
       ),
       'profile'     => function ($options, $option) {
           $profile = $option->getDefault();

           if (is_array($options->commands)) {
               foreach ($options->commands as $command => $class) {
                   $profile->defineCommand($command, $class);
               }
           }

           return $profile
       },
   ));

This is the full list of client options supported by `Client`:

==============  ======================================================  ================================================
option          description                                             default
==============  ======================================================  ================================================
exceptions      Changes how `Client` treats `error replies`_:           true

                - when ``true``, it throws `Predis\\ServerException`.
                - when ``false``, it returns `Predis\\ResponseError`.
--------------  ------------------------------------------------------  ------------------------------------------------
prefix          When set, the passed string is transparently applied    not set
                as a prefix to each key present in command arguments.

                .. note::
                   Keys are prefixed using rules defined by each
                   command in order to be able to support even complex
                   cases such as `SORT`_, `EVAL`_ and `EVALSHA`_.
--------------  ------------------------------------------------------  ------------------------------------------------
profile         Changes the Redis version `Client` is expected to       2.6
                connect to, among a list of :doc:`server profiles`
                predefined by Predis. Supported versions are: ``1.2``,
                ``2.0``, ``2.2``, ``2.4``, ``2.6``, ``dev`` (unstable
                branch in the Redis repository).

                This option accepts also the fully-qualified name of
                a `Predis\\Profile\\ServerProfileInterface`
                or its instance passed either directly or returned by
                a callable initializer.
--------------  ------------------------------------------------------  ------------------------------------------------
connections     Overrides :doc:`connection backends` by scheme using    - tcp: `Predis\\Connection\\StreamConnection`
                a named array, with keys being the connection schemes   - unix: `Predis\\Connection\\StreamConnection`
                subject to change and values being the fully-qualified  - http: `Predis\\Connection\\WebdisConnection`
                name of classes implementing
                `Predis\\Connection\\SingleConnectionInterface`.

                This option accepts also the fully-qualified name of
                a `Predis\\Connection\\ConnectionFactoryInterface`
                or its instance passed either directly or returned by
                a callable initializer.
--------------  ------------------------------------------------------  ------------------------------------------------
cluster         Changes how `Client` handles :doc:`clustering`:         predis

                - ``predis`` indicates the use of client-side
                  sharding.

                - ``redis`` indicates the use `redis cluster`_.

                This option accepts also the fully-qualified name of
                a `Predis\\Connection\\ClusterConnectionInterface`
                or its instance passed either directly or returned by
                a callable initializer.
--------------  ------------------------------------------------------  ------------------------------------------------
replication     When ``true``, the array of connection parameters is    not set
                used in a master and slaves :doc:`replication` setup
                instead of treating the servers as a cluster of nodes.

                This option accepts also the fully-qualified name of
                a `Predis\\Connection\\ReplicationConnectionInterface`
                or its instance passed either directly or returned by
                a callable initializer.
==============  ======================================================  ================================================

.. _error replies: http://redis.io/topics/protocol#status-reply
.. _redis cluster: http://redis.io/topics/cluster-spec
.. _SORT: http://redis.io/commands/eval
.. _EVAL: http://redis.io/commands/eval
.. _EVALSHA: http://redis.io/commands/evalsha
