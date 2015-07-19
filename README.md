drush-psysh
===========

Integration of [psysh][1] into Drush so that you can have a Drupal REPL.
[1]: http://psysh.org/

## Requirements

* [drush][2]
* [composer][3]

[2]: https://github.com/drush-ops/drush
[3]: https://getcomposer.org

## Installation

```bash
drush pm-download drush_psysh
drush cc drush
drush drush-psysh-install-deps
```

## Usage

The two following commands are aliases:
```bash
drush repl
```
or
```bash
drush psysh
```

And here's an example showing the repl in action calling a Drupal function:
![in Action](https://github.com/grota/drush-psysh/raw/7.x-1.x/drush-psysh.png)
