.. vim: set ts=3 sw=3 et :

.. php:namespace:: Predis

Clustering
----------

A "cluster" is a group of Redis servers that collectively provide a shared
namespace. In a cluster, data is not shared between Redis instances; instead,
each server is used to serve a portion of the keys.

In broad terms, this is done by identifying the key part of each Predis command
to be run on the clustered connection. This key is then used to distribute
commands among the underlying connections.

Clustering has obvious advantages. As you add Redis instances to your
cluster, your available space increases. At the same time, commands should be
distributed between the nodes, meaning each individual node has to service
less requests.


Distribution Strategy
=====================

Exactly how keys are split across a cluster is specified by a
:term:`distribution strategy`. There are two distribution strategy classes shipped
with Predis. What they have in common is that they try to manage the task of
adding and removing servers from the cluster cleverly.

When a server is added or removed, the distribution strategy takes care of
ensuring that as few keys as possible have to be moved between the remaining
servers. When you remove a server from a clustered connection of ten servers,
ideally you'd only like 10% of your keys to be newly missing.

This is broadly known as "`consistent hashing`_".

.. _consistent hashing: https://en.wikipedia.org/wiki/Consistent_hashing

Configuring a Cluster
=====================

Recall that the `Client` constructor takes two types of argument: a
set of connection parameters, and a set of options::

   $client = new Predis\Client($connection, $options);

You only need to do one thing differently to set up a clustered client:
identify multiple connections. You can also, optionally, configure your cluster
with the ``cluster`` client option.

Configuring Multiple Connections
''''''''''''''''''''''''''''''''

Passing information about multiple connections is as simple as wrapping them
all in an array::

   $client = new Predis\Client(array(
       array(
           'host'     => '127.0.0.1',
           'port'     => 6379,
           'database' => 2,
           'alias'    => 'first'
       ),
       array(
           'host'     => '127.0.0.1',
           'port'     => 6379,
           'database' => 3,
           'alias'    => 'second',
       )
   ), $options);

You can still use the URI syntax to configure the connections::

   $client = new Predis\Client(array(
       'tcp://127.0.0.1:6370?alias=first&db=0',
       'tcp://127.0.0.1:6370?alias=second&db=1'
   ), $options);

.. note::

   When you want to pass information about multiple connections, Predis expects
   you'll do so with a numeric array, indexed from 0. If you've removed
   connections from your array (perhaps after catching a
   `Predis\\CommunicationException`), you can use array_values() to reindex it.

   If your connection array does not have a value at [0], Predis
   will assume you're trying to configure a single connection with an
   associative array.

The ``cluster`` Client Option
'''''''''''''''''''''''''''''
.. php:namespace:: Predis\Connection

You can optionally confiugre your client's clustering by using the ``cluster``
client option. This option can take a few different types of value. It can take
the special strings ``"predis"`` or ``"redis"`` to switch between the two
built-in cluster connection classes `PredisCluster` and
`RedisCluster` respectively::

   $client = new Predis\Client(array(
       // ...
   ), array('cluster' => 'predis'));

If the value is any other string, it's expected to be the fully qualified name
of a class implementing `ClusterConnectionInterface`.

Finally, you can also configure the option with a callable. This callable is
expected to return an instance of `ClusterConnectionInterface`. The two
built-in clustered connection types both accept a
`Predis\\Cluster\\Distribution\\DistributionStrategyInterface` instance as
their first argument, allowing you to configure that as well::

   $client = new Predis\Client(array(
       // ...
   ), array(
      'cluster' => function () {
         $strategy = new Predis\Cluster\Distribution\KetamaPureRing();
         return new Predis\Connection\PredisCluster($strategy);
      }
   ));

Connection Classes
""""""""""""""""""

.. php:class:: PredisCluster

  ``PredisCluster`` is a simple Predis-native clustered connection implementation.

  This form of clustered connection does *not* provide redundancy. If your application
  makes requests for 100 different keys, with the default distribution strategy
  these keys are likely to be spit across all the servers in your pool.

.. php:class:: RedisCluster

  ``RedisCluster`` is a clustered connection implementation intended for use with
  Redis Cluster. Redis Cluster is not yet finalized, but it already includes some
  pretty cool features. Nodes in a Redis Cluster arrangement configure
  themselves to deal with changes in availability.

Disallowed Commands
===================

Some commands just don't make sense to run on a clustered connection. For
example, the ``INFO`` command returns information about the server on which
it's run, so running it on a cluster would be meaningless.

If you try to run one of these commands, you'll get a
``Predis\\NotSupportedException``.

Running Commands on Nodes
=========================

`PredisCluster` and :php:class:`RedisCluster` both
implement `\\IteratorAggregate`, so you can easily run commands against the
individual Redis servers in a cluster::

   $hosts = array();
   foreach ($client->getConnection() as $shard) {
       $hosts[] = $shard->info();
   }

