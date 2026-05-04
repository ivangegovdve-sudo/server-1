<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Test\Sharing;

use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Permission\ISharePermissionCategoryType;

class TestSharePermissionCategoryType1 implements ISharePermissionCategoryType {
	#[\Override]
	public function getDisplayName(): string {
		/** @var non-empty-list<non-empty-string> $parts */
		$parts = explode('\\', static::class);
		return end($parts);
	}

	#[\Override]
	public function getHint(): string {
		return 'hint ' . $this->getDisplayName();
	}

	#[\Override]
	public function getIcon(): null|ShareIconSVG|ShareIconURL {
		return new ShareIconSVG('<svg/>');
	}

	#[\Override]
	public function getPriority(): int {
		return 1;
	}

	#[\Override]
	public function getDefault(): bool {
		return false;
	}
}
