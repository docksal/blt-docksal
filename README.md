Acquia BLT integration with Docksal
====

This is an [Acquia BLT](https://github.com/acquia/blt) plugin providing [Docksal](https://docksal.io/) integration.

This plugin is **community-created** and **community-supported**. Acquia does not provide any direct support for this software or provide any warranty as to its stability.

## Installation and usage

To use this plugin, you must already have a Drupal project using BLT 10.

In your project, require the plugin with Composer:

`composer require docksal/blt-docksal`

Initialize the Docksal integration by calling `recipes:docksal:project:init`, which is provided by this plugin:

`./vendor/bin/blt recipes:docksal:project:init`

This command will initialize .docksal folder as well as BLT configs in root/blt.
Make sure to commit those changes to Git.

Initialize your project as usually with Docksal fin via `fin init` or repeatedly reinstall your site with `fin init-site`.

`fin init`

Even though the template in this plugin adds a default `fin init` command, you're free to customize it to your likings or delete.

The plugin also installs Docksal blt addon, which makes `fin blt` command available. You can run any BLT command directly in CLI container, for example:

`fin blt tests`

# BLT commands support

- VM commands
Since Docksal is essentially a replacement for DrupalVM support, you shouldn't use `blt vm` with Docksal
BLT project at the time of writing assumes a local DrupalVM is used to run some BLT commands, therefore running a command
in BLT makes a lot of assumptions about locally installed software, specifically Behat commands for example. 
Docksal doesn't have all the same software installed in the [CLI container](https://github.com/docksal/service-cli) and
instead relias on external services available on the docker network for some software support, for example we 
run a chrome service in [docksal.yml](./config/.docksal/docksal.yml), because of that some BLT commands have to be overriden
by Docksal plugin to remove those assumptions about local software. T

- Behat support is implemented with ChromeDriver only, but it's trivial to add other drivers as Docksal services, pull requests welcome!
