<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IUserManager;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\Icon\ShareIconURL;
use RuntimeException;

/**
 * @psalm-import-type SharingOwner from Share
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class ShareOwner {
	public function __construct(
		/** @var non-empty-string $owner */
		public string $userId,
		/** @var ?non-empty-string $instance */
		public ?string $instance,
	) {
		if ($instance !== null && !preg_match('/^https?:\/\/.+/', $instance)) {
			throw new RuntimeException('The instance is not a valid absolute URL: ' . $instance);
		}
	}

	/**
	 * @return SharingOwner
	 */
	public function format(): array {
		$userManager = Server::get(IUserManager::class);
		$ownerUser = $userManager->get($this->userId);
		if ($ownerUser === null) {
			throw new ShareInvalidException('The userId does not exist: ' . $this->userId);
		}

		return [
			'user_id' => $this->userId,
			'instance' => $this->instance,
			'display_name' => $ownerUser->getDisplayName(),
			'icon' => (new ShareIconURL(
				$userManager->getAvatarUrlLight($this->userId, 64),
				$userManager->getAvatarUrlDark($this->userId, 64),
			))->format(),
		];
	}
}
