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


Batch Processor
---------------

A generic batch processor using Queues and Commands.

Should accept a queue or multiple queues to process. Run either as a global standalone or as part of an existing application.

 * getNextJob()
 * isEmpty()
 * addJob($job, $priority=medium)
 
 
BatchJob
--------
 
A wrapper or Command object with enough detail for the task to be carried out later
 
 * jobno (unique job reference number, perhaps a URI?)
 * app name
 * command/task / RPC-like namespace method
 * data
 * status (active, queued, halted, completed, cancelled)
 * retry count
 * create time
 * start time
 * completed time 
 
Should there be an oncomplete to forward this to another processor/queue?
 
Batch Dispatcher
----------------

Dispatches a BatchJob to a particular application-specific method.
Active applications register to an event handler which is
a mapping of supported task/command names.


Batch Queue
-----------

A specialised form of queue, containing multiple queue views.
Each job is added to a queue. Each queue has a priority weighting.

 * jobs (hash of all jobs)
 * queues (references to jobs)
 * active jobs
 * completed jobs
 * logs
 * flag to halt the starting of new jobs
