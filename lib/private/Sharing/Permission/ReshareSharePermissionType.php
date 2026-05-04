<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Sharing\Permission;

use OC\Core\AppInfo\Application;
use OCP\Constants;
use OCP\IAppConfig;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Sharing\Permission\ISharePermissionType;

final class ReshareSharePermissionType implements ISharePermissionType {
	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Share with others');
	}

	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	#[\Override]
	public function getDefault(): bool {
		return (Server::get(IAppConfig::class)->getValueInt(Application::APP_ID, 'shareapi_default_permissions') & Constants::PERMISSION_SHARE) === Constants::PERMISSION_SHARE;
	}

	#[\Override]
	public function getCategory(): string {
		return ShareSharePermissionCategoryType::class;
	}
}
