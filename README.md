## Requirements

- PHP >= 7.1
- Composer - [Install](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

## Installation
ACF PRO 
You specify the acf-pro-key in the config section of your $COMPOSER_HOME/config.json
{
  "config": {
    "acf-pro-key": "Your-Key-Here"
  }
}

$COMPOSER_HOME is a hidden, global (per-user on the machine) directory that is shared between all projects. By default it points to C:\Users\<user>\AppData\Roaming\Composer on Windows and /Users/\<user\>/.composer on macOS. On *nix systems that follow the XDG Base Directory Specifications, it points to $XDG_CONFIG_HOME/composer. On other *nix systems, it points to /home/\<user\>/.composer.

composer create-project justdev/jdwordpress "name of project"
