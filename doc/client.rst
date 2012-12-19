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
password            Password for accessing a password-protected    not set    `Predis\\Connection\\StreamConnection`
                    Redis instance. Its effect is the same of                 `Predis\\Connection\\PhpiredisConnection`
                    using `AUTH`_.
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
.. _AUTH: http://redis.io/commands/auth
.. _Multi-bulk replies: http://redis.io/topics/protocol#multi-bulk-reply
.. _Webdis: http://webd.is/

Users can also specify their own parameters, they will simply be ignored by the
client but can be used later to pass additional information for custom purposes.

Client Options
''''''''''''''


