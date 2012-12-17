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

Client Options
''''''''''''''


