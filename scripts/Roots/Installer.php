<?php

namespace Roots;

use Composer\Script\Event;

class Installer
{
	public static $KEYS = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'];

	public static function addEnv(Event $event)
	{
		$root = dirname(dirname(__DIR__));
		$composer = $event->getComposer();
		$io = $event->getIO();
		$generate_salts = $io->askConfirmation('<info>Generate .env file?</info> [<comment>Y,n</comment>]? ', true);

		if (!$generate_salts) {
			return 1;
		}

		$APP_NAME_ask = $io->ask('<info>Write youre [<comment>APP_NAME</comment>]</info> ');
		if ($APP_NAME_ask) {
			$APP_NAME = "APP_NAME='{$APP_NAME_ask}'\n";
		}

		$PHPMYADMIN_PORT_ask = $io->ask('<info>Write youre [<comment>PHPMYADMIN_PORT</comment>]</info> ');
		if ($PHPMYADMIN_PORT_ask) {
			$PHPMYADMIN_PORT = "PHPMYADMIN_PORT={$PHPMYADMIN_PORT_ask}\n";
		}

		$BACKEND_PORT_ask = $io->ask('<info>Write youre [<comment>BACKEND_PORT</comment>]</info> ');
		if ($BACKEND_PORT_ask) {
			$BACKEND_PORT = "BACKEND_PORT={$BACKEND_PORT_ask}\n";
		}

		$DB_NAME = "DB_NAME='{$APP_NAME_ask}'\n";

		$DB_USER = "DB_USER=root\n";
		$DB_PASSWORD = "DB_PASSWORD=dev\n";
		$DB_HOST = "DB_HOST=${APP_NAME_ask}.mariadb:3306\n";

		$DB_PORT_ask = $io->ask('<info>Write youre [<comment>DB_PORT</comment>]</info> ');
		if ($DB_PORT_ask) {
			$DB_PORT = "DB_PORT={$DB_PORT_ask}\n";
		}

		$WP_ENV_ask = $io->ask('<info>Write youre [<comment>WP_ENV: development/staging/production</comment>]</info> ');
		if ($WP_ENV_ask) {
			$WP_ENV = "WP_ENV='{$WP_ENV_ask}'\n";
		}

		$ENV_DEVELOPMENT = "ENV_DEVELOPMENT='http://localhost:${BACKEND_PORT_ask}'\n";

		$ENV_STAGING_ask = $io->ask('<info>Write youre [<comment>ENV_STAGING</comment>]</info> ');
		if ($ENV_STAGING_ask) {
			$ENV_STAGING = "ENV_STAGING='{$ENV_STAGING_ask}'\n";
		}

		$ENV_PRODUCTION_ask = $io->ask('<info>Write youre [<comment>ENV_PRODUCTION</comment>]</info> ');
		if ($ENV_PRODUCTION_ask) {
			$ENV_PRODUCTION = "ENV_PRODUCTION='{$ENV_PRODUCTION_ask}'\n";
		}

		$env_content =
			$APP_NAME .
			$PHPMYADMIN_PORT .
			$BACKEND_PORT .
			$DB_NAME .
			$DB_USER .
			$DB_PASSWORD .
			$DB_HOST .
			$DB_PORT .
			$WP_ENV .
			$ENV_DEVELOPMENT .
			$ENV_STAGING .
			$ENV_PRODUCTION;

		$env_file = "{$root}/.env";
		file_put_contents($env_file, $env_content);
	}

	public static function addSalts(Event $event)
	{
		$root = dirname(dirname(__DIR__));
		$composer = $event->getComposer();
		$io = $event->getIO();

		if (!$io->isInteractive()) {
			$generate_salts = $composer->getConfig()->get('generate-salts');
		} else {
			$generate_salts = $io->askConfirmation('<info>Generate salts and append to .env file?</info> [<comment>Y,n</comment>]? ', true);
		}

		if (!$generate_salts) {
			return 1;
		}

		$salts = array_map(function ($key) {
			return sprintf("%s='%s'", $key, Installer::generateSalt());
		}, self::$KEYS);

		$env_file = "{$root}/.env";

		if (file_exists($env_file)) {
			file_put_contents($env_file, implode($salts, "\n"), FILE_APPEND | LOCK_EX);
		} else {
			if (copy("{$root}/.env.example", $env_file)) {
				file_put_contents($env_file, implode($salts, "\n"), FILE_APPEND | LOCK_EX);
			} else {
				$io->write('<error>An error occured while copying your .env file</error>');
				return 1;
			}
		}
	}

	/**
	 * Slightly modified/simpler version of wp_generate_password
	 * https://github.com/WordPress/WordPress/blob/cd8cedc40d768e9e1d5a5f5a08f1bd677c804cb9/wp-includes/pluggable.php#L1575
	 */
	public static function generateSalt($length = 64)
	{
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$chars .= '!@#$%^&*()';
		$chars .= '-_ []{}<>~`+=,.;:/?|';

		$salt = '';
		for ($i = 0; $i < $length; $i++) {
			$salt .= substr($chars, rand(0, strlen($chars) - 1), 1);
		}

		return $salt;
	}
}
