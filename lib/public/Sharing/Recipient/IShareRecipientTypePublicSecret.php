<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Recipient;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface IShareRecipientTypePublicSecret extends IShareRecipientType {
	public function isSecretPublic(string $recipient): bool;

	public function isSecretUpdatable(string $recipient): bool;
}
