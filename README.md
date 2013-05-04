MySQL Profiler
==================================================
A SQL profiler for WordPress

Description
-----------

MySQL Profiler displays debugging information about SQL queries to admin WordPress users.  For each query, the profiler displays the time, syntax-highlighted SQL, and a trace of the functions (as well as the file and line number that the functions were called from) that were called. Additionally, for SELECT queries, the profiler calls 'EXPLAIN SELECT ...' to get query plan. This is particularly useful to identify problematic queries that do not use any kind of indexes.

The list can be sorted by any of its columns, so you can, for example:

* Order the list by ID to see the chronological order of the queries
* Order the list by time to see the slowest queries
* Order the list by query to group the queries by type (SELECT, UPDATE, etc)
* Order the list by trace to group the queries by similar origins

The list can also filtered by typing in the search box, so you can, for example:

* View all queries that use the wp_posts table
* View all queries that are related to the use of a function in taxonomy.php
* View all queries that are related to the use of a specific function or class
* View all queries that call a specific MySQL function

To turn off syntax highlighting, put the following in wp-config.php:

    define('MP_HIGHLIGHT_SYNTAX', false);

To omit the file and line number from the function trace and display the functions as a comma-separated list (to save vertical space), define the following in wp-config.php:

    define('MP_DISPLAY_FILES', false);

To turn off EXPLAIN query plan, define the following in wp-config.php:

    define('MP_EXPLAIN', false);

This plugin was loosely based on [Frank Bueltge](http://bueltge.de/)'s [Debug Queries](http://wordpress.org/extend/plugins/debug-queries/) plugin, so a hearty thanks to him for the development of that.

If you'd like to grab development releases, see what new features are being added, or browse the source code please visit/follow the [GitHub repo](http://github.com/tombenner/mysql-profiler).

Installation
------------

1. Put `mysql-profiler` into the `wp-content/plugins` directory
1. Activate the plugin in the "Plugins" menu in WordPress to turn on the profiling
1. Deactivate when you've finished using the profiling

Frequently Asked Questions
--------------------------

#### Is feature X available?

If there's widely-needed functionality that you'd like that isn't implemented, I'd likely be willing to implement it myself or to accept any well-written code that implements it. Please feel free to either add a topic in the WordPress forum or contact me through GitHub for any such requests:

* [WordPress Forum](http://wordpress.org/tags/mysql-profiler?forum_id=10)
* [GitHub](http://github.com/tombenner)