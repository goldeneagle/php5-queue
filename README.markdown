PHP5 Queue
==========

A simple persistent queue with a extensible backend storage.

Each object that needs to be queued needs to have some
unique identifier, or implements Queueable

A SerialisedQueueStorage keeps track of a queue using
a serialisable php object.

Features
--------

 * Priority levels
 * Persistence
 * Iteratable
 * Completion Flag


