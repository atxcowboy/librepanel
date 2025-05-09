<?php

/**
 * This file is part of the LibrePanel project.
 * Copyright (c) 2010 the LibrePanel Team (see authors).
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, you can also view it online at
 * https://files.librepanel.org/misc/COPYING.txt
 *
 * @copyright  the authors
 * @author     LibrePanel team <team@librepanel.org>
 * @license    https://files.librepanel.org/misc/COPYING.txt GPLv2
 */

namespace LibrePanel\Cli;

use Exception;
use LibrePanel\Api\Commands\Admins;
use LibrePanel\Api\Commands\Customers;
use LibrePanel\LibrePanel;
use LibrePanel\System\Crypt;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class UserCommand extends CliCommand
{

	protected function configure()
	{
		$this->setName('librepanel:user');
		$this->setDescription('Various user actions');
		$this->addArgument('user', InputArgument::REQUIRED, 'Loginname of the target user')
			->addArgument('admin', InputArgument::OPTIONAL, 'Loginname of the executing admin/reseller user', 'admin');
		$this->addOption('unlock', 'u', InputOption::VALUE_NONE, 'Unlock user after too many failed login attempts')
			->addOption('change-passwd', 'p', InputOption::VALUE_NONE, 'Set new password for given user')
			->addOption('show-info', 's', InputOption::VALUE_NONE, 'Output information details of given user');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$result = self::SUCCESS;

		$result = $this->validateRequirements($output);

		require LibrePanel::getInstallDir() . '/lib/functions.php';

		// set error-handler
		@set_error_handler([
			'\\LibrePanel\\Api\\Api',
			'phpErrHandler'
		]);

		if ($result == self::SUCCESS) {
			try {
				$adminname = $input->getArgument('admin');
				$admininfo = $this->getUserByName($adminname);
				$loginname = $input->getArgument('user');
				$userinfo = $this->getUserByName($loginname, false);

				$do_unlock = $input->getOption('unlock');
				$do_passwd = $input->getOption('change-passwd');
				$do_show = $input->getOption('show-info');

				if ($do_unlock === false && $do_passwd === false && $do_show === false) {
					$output->writeln('<error>No option given, nothing to do.</>');
					$result = self::INVALID;
				}

				if ($do_unlock !== false) {
					// unlock given user
					if ((int)$userinfo['adminsession'] == 1) {
						Admins::getLocal($admininfo, ['loginname' => $loginname])->unlock();
					} else {
						Customers::getLocal($admininfo, ['loginname' => $loginname])->unlock();
					}
					$output->writeln('<info>User ' . $loginname . ' unlocked successfully</>');
					$result = self::SUCCESS;
				}
				if ($result == self::SUCCESS && $do_passwd !== false) {
					$io = new SymfonyStyle($input, $output);
					$passwd = $io->askHidden("Enter new password", function ($value) {
						if (empty($value)) {
							throw new \RuntimeException('You must enter a value.');
						}
						$value = Crypt::validatePassword($value, 'new password');
						return $value;
					});
					$passwd2 = $io->askHidden("Confirm new password", function ($value) use ($passwd) {
						if (empty($value)) {
							throw new \RuntimeException('You must enter a value.');
						} elseif ($value != $passwd) {
							throw new \RuntimeException('Passwords do not match');
						}
						return $value;
					});
					if ((int)$userinfo['adminsession'] == 1) {
						Admins::getLocal($admininfo, ['id' => $userinfo['adminid'], 'admin_password' => $passwd])->update();
					} else {
						Customers::getLocal($admininfo, ['id' => $userinfo['customerid'], 'new_customer_password' => $passwd])->update();
					}
					$output->writeln('<info>Changed password for ' . $loginname . '</>');
					$result = self::SUCCESS;
				}
				if ($result == self::SUCCESS && $do_show !== false) {
					$userinfo['password'] = '[hidden]';
					$userinfo['data_2fa'] = '[hidden]';
					$io = new SymfonyStyle($input, $output);
					$io->horizontalTable(
						array_keys($userinfo),
						[array_values($userinfo)]
					);
				}
			} catch (Exception $e) {
				$output->writeln('<error>' . $e->getMessage() . '</>');
				$result = self::FAILURE;
			}
		}

		return $result;
	}
}
