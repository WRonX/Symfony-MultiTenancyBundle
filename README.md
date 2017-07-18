#### Poor Man's Symfony 2.8x Multitenancy

This piece of poorly written code may help you with creating multi-tenant applications in Symfony 2.8x. Or may not. I don't know, I'm a plumber, not a fortune-teller.


#### License:

> Copyright © 2016 github.com/WRonX
This work is free. You can redistribute it and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See http://www.wtfpl.net/ for more details.


#### Features:

Just one: you can enable multi-tenant architecture in your application.


#### How it works

I couldn't think of better solution, so every tenant has a name and host, by which it's identified. Name is used to identify tenant when using Symfony console, host for everything else. In general, `ConnectionWrapper` changes `Connection` database, depending on currently used host.
> **NOTE:** In performance matter, this is probably not the best idea for bigger applications and **you probably should cache Connection somehow to prevent it from making tenant identifying SQL request every time it's created.** But what do I know.


#### Installation and Configuration:

First, in your `config.yml` set `wrapper_class` for doctrine:

```
# app/config/config.yml

doctrine:
    dbal:
        wrapper_class: "%connection_wrapper%"
```

If you want to stay in single-tenant mode, just change `connection_wrapper` to `null` in `parameters.yml`. Other parameters should point to application's database, as usual.
In order to use multi-tenant mode, follow the steps below:

##### 1. Preparing Tenant Manager database:

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

#### Summary

Oh, come on, I spent enough time writing readme already...

