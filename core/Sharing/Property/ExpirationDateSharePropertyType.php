<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use OC\Core\AppInfo\Application;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Sharing\Property\ADateSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use RuntimeException;

final readonly class ExpirationDateSharePropertyType extends ADateSharePropertyType implements ISharePropertyTypeFilter {
	private IManager $legacyManager;

	private DateTimeImmutable $now;

	public function __construct() {
		$this->legacyManager = Server::get(IManager::class);
		$this->now = new DateTimeImmutable();
	}

	#[\Override]
	public function getDisplayName(): string {
		return Server::get(IFactory::class)->get(Application::APP_ID)->t('Expiration date');
	}

	#[\Override]
	public function getHint(): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 70;
	}

	#[\Override]
	public function getRequired(): bool {
		if ($this->legacyManager->shareApiLinkDefaultExpireDateEnforced()) {
			return true;
		}

		if ($this->legacyManager->shareApiRemoteDefaultExpireDateEnforced()) {
			return true;
		}

		return $this->legacyManager->shareApiInternalDefaultExpireDateEnforced();
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		return $this->getDefault()?->format(DateTimeInterface::ATOM);
	}

	#[\Override]
	public function getMinDate(): ?DateTimeImmutable {
		if ($this->getRequired()) {
			return $this->getDefault();
		}

		// Ensure the expiration date is in the future.
		return $this->now->add(new DateInterval('PT5M'));
	}

	#[\Override]
	public function getMaxDate(): ?DateTimeImmutable {
		if ($this->getRequired()) {
			// Allow some time to pass between the user getting the max date and saving the date, as the time will shift in between.
			return $this->getDefault()?->add(new DateInterval('PT5M'));
		}

		return null;
	}

	private function getDefault(): ?DateTimeImmutable {
		// We do not have any distinction between link/remote/internal, so we just apply the lowest expiration days count to be safe.
		$days = INF;
		if ($this->legacyManager->shareApiLinkDefaultExpireDate()) {
			$days = min($days, $this->legacyManager->shareApiLinkDefaultExpireDays());
		}

		if ($this->legacyManager->shareApiRemoteDefaultExpireDate()) {
			$days = min($days, $this->legacyManager->shareApiRemoteDefaultExpireDays());
		}

		if ($this->legacyManager->shareApiInternalDefaultExpireDate()) {
			$days = min($days, $this->legacyManager->shareApiInternalDefaultExpireDays());
		}

		if ($days !== INF) {
			return $this->now->add(new DateInterval('P' . $days . 'D'));
		}

		return null;
	}

	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		if (($property = $share->properties[self::class] ?? null) !== null && $property->value !== null) {
			$date = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $property->value);
			if ($date === false) {
				throw new RuntimeException('Invalid date.');
			}

			return $this->now->diff($date)->invert === 1;
		}

		return false;
	}
}
