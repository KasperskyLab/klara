# Database requirements

Please install a MySQL database (we recommend MariaDB) and make it accessible for Dispatcher 
and Web Interface. 

To create a new DB user, allowing access to `klara` database, for any hosts, identified by password `pass12345` use the following command:

```
##### For the klara DB #####
# Create a random password for the user klara for the DB klara
CREATE USER 'klara'@'127.0.0.1' IDENTIFIED BY 'pass12345';
GRANT USAGE ON *.* TO 'klara'@'127.0.0.1' IDENTIFIED BY 'pass12345' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0;
GRANT ALL PRIVILEGES ON `klara`.* TO 'klara'@'127.0.0.1';

```

Once Dispatcher and Web Interfaces are set-up and configured to point to the DB, the MySQL schema needs to be created. Please run all SQL statements from [db_patches](db_patches/) folder sorted chronologically by their revisions.

If you want a lost of all the commands in one file, please run:
```
cat * > db.schema.sql
mysql [connecting options] < db.schema.sql