<?php

namespace Roots;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class Installer
{
	public static $KEYS = ['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'];

	public static function addTest(Event $event)
	{
		$root = dirname(dirname(__DIR__));
		$composer = $event->getComposer();
		$io = $event->getIO();

		$folder_name = basename(dirname(dirname(__DIR__)));

		$char = ['.', ' '];
		$project_name = str_replace($char, '', $folder_name);
	}
	public static function addEnv(Event $event)
	{
		$root = dirname(dirname(__DIR__));
		$composer = $event->getComposer();
		$io = $event->getIO();

		$folder_name = basename(dirname(dirname(__DIR__)));

		$char = ['.', ' '];
		$project_name = str_replace($char, '', $folder_name);

		// NOTE: DDEV config
		$ddevconfig = "{$root}/.ddev/config.yaml";
		if (file_exists($ddevconfig)) {
			$NAME = "name: {$project_name}\n";

			$STANDART = file_get_contents($ddevconfig);

			$ddevconfig_content = $NAME . $STANDART;

			file_put_contents($ddevconfig, $ddevconfig_content);
		}

		// NOTE: vscode workspace config
		$workspace_file = "{$root}/wordpress_development.code-workspace";
		if (file_exists($workspace_file)) {
			$workspace = file_get_contents($workspace_file);

			$newworkspace = str_replace('_juststart', $project_name, $workspace);

			file_put_contents($workspace_file, $newworkspace);
			rename($workspace_file, "{$project_name}.code-workspace");
		}

		// NOTE: .env config
		$generate_env = $io->askConfirmation('<info>Generate .env file?</info> [<comment>Y,n</comment>]? ', true);

		if (!$generate_env) {
			return 1;
		}

		$DB_NAME_ask = $io->ask('<info>Write youre [<comment>DB_NAME</comment>] (empty: db)</info> ');
		if ($DB_NAME_ask) {
			$DB_NAME = "DB_NAME='{$DB_NAME_ask}'\n";
		} else {
			$DB_NAME = "DB_NAME='db'\n";
		}

		$DB_USER_ask = $io->ask('<info>Write youre [<comment>DB_USER</comment>] (empty: db)</info> ');
		if ($DB_USER_ask) {
			$DB_USER = "DB_USER='{$DB_USER_ask}'\n";
		} else {
			$DB_USER = "DB_USER='db'\n";
		}

		$DB_PASSWORD_ask = $io->ask('<info>Write youre [<comment>DB_PASSWORD</comment>] (empty: db)</info> ');
		if ($DB_PASSWORD_ask) {
			$DB_PASSWORD = "DB_PASSWORD='{$DB_PASSWORD_ask}'\n";
		} else {
			$DB_PASSWORD = "DB_PASSWORD='db'\n";
		}

		$DB_HOST_ask = $io->ask('<info>Write youre [<comment>DB_HOST</comment>] (empty: db)</info> ');
		if ($DB_HOST_ask) {
			$DB_HOST = "DB_HOST='{$DB_HOST_ask}'\n";
		} else {
			$DB_HOST = "DB_HOST='db'\n";
		}

		$WP_ENV_ask = $io->ask('<info>Write youre [<comment>WP_ENV: development / staging / production</comment>]</info> ');
		if ($WP_ENV_ask) {
			$WP_ENV = "WP_ENV='{$WP_ENV_ask}'\n";
		} else {
			$WP_ENV = "WP_ENV='development'\n";
		}

		$ENV_DEVELOPMENT = "ENV_DEVELOPMENT='https://${project_name}.ddev.site'\n";

		$ENV_STAGING_ask = $io->ask('<info>Write youre [<comment>ENV_STAGING</comment>]</info> ');
		if ($ENV_STAGING_ask) {
			if (strpos($ENV_STAGING_ask, 'http://') && strpos($ENV_STAGING_ask, 'https://')) {
				$ENV_STAGING = "ENV_STAGING='{$ENV_STAGING_ask}'\n";
			} else {
				$ENV_STAGING = "ENV_STAGING='https://{$ENV_STAGING_ask}'\n";
			}
		} else {
			$ENV_STAGING = "ENV_STAGING='#'\n";
		}

		$ENV_PRODUCTION_ask = $io->ask('<info>Write youre [<comment>ENV_PRODUCTION</comment>]</info> ');
		if ($ENV_PRODUCTION_ask) {
			if (strpos($ENV_PRODUCTION_ask, 'http://') && strpos($ENV_PRODUCTION_ask, 'https://')) {
				$ENV_PRODUCTION = "ENV_PRODUCTION='{$ENV_PRODUCTION_ask}'\n";
			} else {
				$ENV_PRODUCTION = "ENV_PRODUCTION='https://{$ENV_PRODUCTION_ask}'\n";
			}
		} else {
			$ENV_PRODUCTION = "ENV_PRODUCTION='#'\n";
		}

		$env_content = $DB_NAME . $DB_USER . $DB_PASSWORD . $DB_HOST . $WP_ENV . $ENV_DEVELOPMENT . $ENV_STAGING . $ENV_PRODUCTION;

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
			file_put_contents($env_file, implode("\n", $salts), FILE_APPEND | LOCK_EX);
		} else {
			if (copy("{$root}/.env.example", $env_file)) {
				file_put_contents($env_file, implode("\n", $salts), FILE_APPEND | LOCK_EX);
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
