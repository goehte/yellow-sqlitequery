# SQLite Query
SQLite Query Extension for Datenstrom Yellow

An extension for [Datenstrom Yellow](https://datenstrom.se/yellow/) CMS that allows you to execute SQLite queries directly within your Markdown content and automatically renders the results into a clean HTML table.

## Features
* **Inline Queries:** Run short SQL queries directly inside your Markdown.
* **External SQL Files:** Keep complex or long queries organized in separate `.sql` files.


## Installation

1. Copy `sqlitequery.php` into your `system/plugins/` directory.
2. Create the required storage directories in your `media/` folder (see below).


## Usage
You can invoke the plugin inside any Markdown page using the `[sqlitequery]` shortcut.
1. Using Inline SQL
```
[sqlitequery production.sqlite "SELECT name, email FROM users WHERE active = 1"]
```
2. Using an External SQL File
```
[sqlitequery production.sqlite users_overview.sql]
```


## Directories

The extension expects your databases and query files to be organized under the `media/` folder:

```text
root/
└── media/
    └── databases/
        ├── production.sqlite        <-- Your SQLite databases
        └── sql/
            └── users_overview.sql   <-- Your .sql query files 
```

**Made with 💛 for the Yellow CMS Community**
