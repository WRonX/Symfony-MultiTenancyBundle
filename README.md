#### Poor Man's Symfony Multitenancy Bundle

This piece of poorly written code may help you with creating multi-tenant applications in Symfony. Or may not. I don't know, I'm a plumber, not a fortune-teller.


#### License:

> Copyright © 2017 github.com/WRonX
This work is free. You can redistribute it and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.


#### Features:

Just one: you can enable multi-tenant architecture in your application.


#### How it works

I couldn't think of better solution, so every tenant has a name and host, by which it's identified. Name is used to identify tenant when using Symfony console, host for everything else. In general, `ConnectionWrapper` changes `Connection` database, depending on currently used host.
> **NOTE:** In performance matter, this is probably not the best idea for bigger applications and **you probably should cache Connection somehow to prevent it from making tenant identifying SQL request every time it's created.** But what do I know.


#### Installation and Configuration:

##### 1. Installing the bundle

First, install the bundle with `composer`:

```
composer require wronx/multitenancy-bundle
```

Then add the bundle to `AppKernel`:

```
// app/AppKernel.php

$bundles = array( /* ... */
            new WRonX\MultiTenancyBundle\WRonXMultiTenancyBundle(),
```

And add the following do your `config.yml`:

```
# app/config/config.yml

wronx_multitenancy:
    enabled: true
```

If you skip the last step, multitenancy will be disabled by default.

> **NOTE:** With disabled multitenancy, your application uses the main (described in `parameters.yml`) database in normal way. 

The following steps are assuming multitenancy is enabled.

##### 2. Prepare Tenant Manager database:

First, create database, which will serve as Tenant Manager. That means connection details will be stored there. Passwords will be stored in plaintext, just like DB password in `parameters.yml`. The `parameters.yml` connection details should point to Tenant Manager database.
Now, Tenant Manager database should contain `tenants` table with connection details for every tenant:

```
mysql> SHOW COLUMNS FROM tenants;
+--------+--------------+------+-----+---------+----------------+
| Field  | Type         | Null | Key | Default | Extra          |
+--------+--------------+------+-----+---------+----------------+
| id     | int(11)      | NO   | PRI | NULL    | auto_increment |
| name   | varchar(255) | YES  |     | NULL    |                |
| host   | varchar(255) | YES  |     | NULL    |                |
| dbName | varchar(255) | YES  |     | NULL    |                |
| dbPass | varchar(255) | YES  |     | NULL    |                |
| dbUser | varchar(255) | YES  |     | NULL    |                |
| dbHost | varchar(255) | YES  |     | NULL    |                |
| active | tinyint(1)   | YES  |     | NULL    |                |
+--------+--------------+------+-----+---------+----------------+
```

As `ConnectionWrapper` uses `REGEXP`, **host field** can look like the following:
* `.*`
* `^(.*\.)*client01\.yourapplicationdomain\.com$`
* `^((.*\.)*client02\.yourapplicationdomain\.com)|(clientsowndomain\.com)$`

As you can see from this example, you can handle multime client domains, and subdomains can be ignored. Also, `SELECT` query is ordered by host field's length (descending), so every request to non-existing host (tenant) will be handled by connection data defined in record with `.*` host, which can be useful for creating demo environment.
Many thanks to [swiniak](https://github.com/swiniak/) for coming up with `REGEXP` idea.

##### 2. Using included code and configuration:

This should be self-explanatory.

##### 3. Using Symfony console:

Just remember to add tenant name for every command, using `--tenant=TENANTNAME` (this is the *name* field from `tenants` table).

##### 4. New console commands

Two new console commands were added:

* `tenants:list` just shows available tenant names (and some additional data)
* `tenants:execute "command to execute"` executes given (quoted!) command on all tenants. Example: `php app/console tenants:execute "doctrine:schema:update --dump-sql"`

#### Changes coming soon

* adding console commands to automatically create tenants table and manage tenants
* changing ignored commands list into commands whitelist


#### Summary

Oh, come on, I spent enough time writing readme already...

