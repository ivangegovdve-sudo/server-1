<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Recipient;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Sharing\Exception\ShareInvalidException;
use OCP\Sharing\IRegistry;
use OCP\Sharing\Share;
use RuntimeException;

/**
 * @psalm-import-type SharingRecipient from Share
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class ShareRecipient {
	public function __construct(
		/** @var class-string<IShareRecipientType> $class */
		public string $class,
		/** @var non-empty-string $value */
		public string $value,
		/** @var ?non-empty-string $instance */
		public ?string $instance,
		/** @var ?non-empty-string $id */
		public ?string $id = null,
		/** @var ?non-empty-string $parentId */
		public ?string $parentId = null,
		/** @var ?non-empty-string $secret */
		public ?string $secret = null,
	) {
		if ($instance !== null && !preg_match('/^https?:\/\/.+/', $instance)) {
			throw new RuntimeException('The instance is not a valid absolute URL: ' . $instance);
		}
	}

	/**
	 * @return SharingRecipient
	 */
	public function format(bool $isUnique): array {
		if (($recipientType = (Server::get(IRegistry::class)->getRecipientTypes()[$this->class] ?? null)) === null) {
			throw new ShareInvalidException('The recipient type is not registered: ' . $this->class);
		}

		$displayName = $recipientType->getRecipientDisplayName($this->value) ?? $this->value;
		if (!$isUnique) {
			$displayName .= ' (' . $recipientType->getDisplayName() . ': ' . $this->value . ')';
		}

		$secret = [
			'updatable' => $recipientType instanceof IShareRecipientTypePublicSecret && $recipientType->isSecretUpdatable($this->value),
		];
		if ($this->secret !== null && $recipientType instanceof IShareRecipientTypePublicSecret && $recipientType->isSecretPublic($this->value)) {
			$secret['value'] = $this->secret;

			// TODO: We need a dedicated page that shows all sources linked to a share
			$url = Server::get(IURLGenerator::class)->linkToRouteAbsolute(
				'files_sharing.sharecontroller.showShare',
				[
					'token' => $this->secret,
				],
			);
			if ($url === '') {
				throw new RuntimeException('The URL is empty.');
			}

			$secret['url'] = $url;
		}

		return [
			'class' => $this->class,
			'value' => $this->value,
			'instance' => $this->instance,
			'display_name' => $displayName,
			'icon' => $recipientType->getRecipientIcon($this->value)?->format(),
			'secret' => $secret,
		];
	}

	/**
	 * @param list<self> $recipients
	 * @return list<SharingRecipient>
	 */
	public static function formatMultiple(array $recipients): array {
		$recipientTypes = Server::get(IRegistry::class)->getRecipientTypes();

		$recipientDisplayNames = [];
		foreach ($recipients as $recipient) {
			$displayName = $recipientTypes[$recipient->class]?->getRecipientDisplayName($recipient->value) ?? $recipient->value;
			$recipientDisplayNames[$displayName] ??= 0;
			++$recipientDisplayNames[$displayName];
		}

		return array_map(static fn (ShareRecipient $recipient): array => $recipient->format($recipientDisplayNames[$recipientTypes[$recipient->class]?->getRecipientDisplayName($recipient->value) ?? $recipient->value] === 1), $recipients);
	}
}
