<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use OC\Core\AppInfo\Application;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IEmailValidator;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;

// TODO: Add logic to send emails when share state is updated to active

final class EmailShareRecipientType extends AShareRecipientTypeSearchCollaborator {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Email');
	}

	#[\Override]
	public function validateRecipient(IUser $owner, string $recipient): bool {
		return Server::get(IEmailValidator::class)->isValid($recipient);
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		return [];
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): string {
		return $recipient;
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_EMAIL;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'emails';
	}
}
