<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconURL;

// TODO: Add delete listener to remove recipients
final class UserShareRecipientType extends AShareRecipientTypeSearchCollaborator {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('User');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		if ($recipient === $owner->getUID()) {
			return false;
		}

		$recipientUser = Server::get(IUserManager::class)->get($recipient);
		if ($recipientUser === null) {
			return false;
		}

		$legacyManager = Server::get(IManager::class);
		if ($legacyManager->shareWithGroupMembersOnly()) {
			$groupManager = Server::get(IGroupManager::class);

			$groups = array_intersect(
				$groupManager->getUserGroupIds($owner),
				$groupManager->getUserGroupIds($recipientUser),
			);
			if ($groups === []) {
				return false;
			}

			$groups = array_diff($groups, $legacyManager->shareWithGroupMembersOnlyExcludeGroupsList());
			if ($groups === []) {
				return false;
			}
		}

		return true;
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return [$currentUser->getUID()];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		return Server::get(IUserManager::class)->getDisplayName($recipient);
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): ShareIconURL {
		$userManager = Server::get(IUserManager::class);

		return new ShareIconURL(
			$userManager->getAvatarUrlLight($recipient, 64),
			$userManager->getAvatarUrlDark($recipient, 64),
		);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_USER;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'users';
	}
}
