# allegrowatcher
This is simple application which allows to track new auctions.
allegrowatcher is app which runs allegro subscriptions - it sends email each time new item is put in allegro shop.

## How to install

To install allegro watcher, ensure that your have:
* installed php in version 5.3 or greater,
* configured mail command.

Please run this command (Unix systems):

`wget https://raw.githubusercontent.com/jagusiak/allegrowatcher/master/install.sh -O aw_installer.sh && sh aw_installer.sh && rm aw_installer.sh`

Your version of allegro watcher is installed in directory `allegrowatcher/`

## How to use?

To list possible commands please run:

`php allegro_watcher.php`

### Setup allegro key

To run application, you need to pass allegro api key. It can be obtained in allegro profile: [allegro api key](http://allegro.pl/myaccount/webapi.php/).

Run command:

`php allegro_watcher.php config key YOUR_CODE`

### Setup country code (OPTIONAL)

You would need to change your allegro country (by default - it is polish). You can obtain possible country codes using command:

`php allegro_watcher.php codes`

Run command (to set country)

`php allegro_watcher.php config code COUNTRY_CODE`

**NOTE: Allegro doesn't have shop in many countries!**

### Create allegro query

To create allegro query run following command:

`php allegro_watcher.php set "search" query-id`

where:
_search_ - query searched on allegro
_query-id_ - identifies query (if you will leave it empty, it will assign integer default value)

If you would run command `set` using existing _query-id_, previous query will be updated with new values.

### List available queries

To list all queries run this command:

`php allegro_watcher.php show`

### Remove query

To remove query run this command:

`php allegro_watcher.php del query-id`

where
_query-id_ - identifier of query (explained earlier)

### Add subscriber

To add subscription to given query, run query:

`php allegro_watcher.php subscribe email-address query-id`

where
_email-address_ - address where emails will be sent
_query-id_ - identifier of query (explained earlier)

You may add custom message to email by adding additional command parameter:

`php allegro_watcher.php subscribe --message="Custom message" email-address query-id`

### List all query subscription

To list all subscription, run command:

`php allegro_watcher.php emails query-id`

where
_query-id_ - identifier of query (explained earlier)

### Remove subscription

To remove subscription, please use command:

`php allegro_watcher.php unsubscribe email-adress query-id`

where
_email-address_ - address where emails will be not sent
_query-id_ - identifier of query (explained earlier)

### Running queries

To execute query, run command:

`php allegro_watcher.php run query-id`

where
_query-id_ - identifier of query (explained earlier)

This operation will send email with new position on allegro (determined by query). Running it again, will only send information only with new position (added after previous run).

To run all queries, use shorter command:

`php allegro_watcher.php run`


## Advanced usage

Here is list of advanced options.

### Get filters

To obtain possible filters for query, please run command:

`php allegro_watcher.php filters query`

where
_query_ - is query which will be run in allegro

Given command returns list of filters.

## Creating filter string

Filter string is created in format:

> filter1=value1,value2;filter2=[10,23];filter3=value3,value4;..

Each filter is delimited by ';'. Filters' names can be obtained by previous command. Filter value is set after sign '='. Values can be represented in two types:
* list - values delimited by ',' (if there is one value, it doesn't need separator)
* range - values written in format: [min,max], which represent range from min to max (it is indicated by '=[min,max]' in previous command).

Example
> price=[10,100];condition=new;state=3,6,10

it will choose positions with price between values 10 and 100, with condition new and states represented by values: 3,6,10 (can be read from previous command)

### Apply filters

To apply filters, use extended set command:

`php allegro_watcher.php set --filters="filter-string" "search" query-id`

where:
_filter-string_ - filter string (explained in previous section)
_search_ - query searched on allegro
_query-id_ - identifies query (if you will leave it empty, it will assign integer default value)


### Category filter

To use category filter, please add line 
> category=CATEGORY-ID

CATEGORY-ID can be obtained by running command:

`php allegro_watcher.php categories`

It will return list of id and related categories.
We suggest storing its output (command is really slow):

`php allegro_watcher.php categories > categories.txt`

To find category id, you may grep created file"

`grep CATEGORY-NAME categories.txt`

## CRON

We suggest setting cron job with allegrowatcher to run periodic subscription.

It can be done by command:

`crontab -e`

Please add line:

`0 21 * * * php /path_to_allegro_watcher/allegro_watcher.php run`

and save. With this configuration, script will be run every day on 9 p.m. Please read more about cron for more conscious configuration ([cron setup](http://www.cyberciti.biz/faq/how-do-i-add-jobs-to-cron-under-linux-or-unix-oses/))  
