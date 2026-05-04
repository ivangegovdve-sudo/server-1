<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing\Permission;

use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\Permission\ISharePermissionCategoryType;

final class ShareSharePermissionCategoryType implements ISharePermissionCategoryType {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Share');
	}

	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	#[\Override]
	public function getIcon(): null|ShareIconSVG|ShareIconURL {
		// TODO: Implement getIcon() method.
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 100;
	}

	#[\Override]
	public function getDefault(): bool {
		return false;
	}
}
