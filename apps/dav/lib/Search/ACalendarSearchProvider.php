<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Search;

use OCA\DAV\CalDAV\CalDavBackend;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Search\IProvider;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Reader;

/**
 * Class ACalendarSearchProvider
 *
 * @package OCA\DAV\Search
 */
abstract class ACalendarSearchProvider implements IProvider {

	/**
	 * ACalendarSearchProvider constructor.
	 *
	 * @param IAppManager $appManager
	 * @param IL10N $l10n
	 * @param IURLGenerator $urlGenerator
	 * @param CalDavBackend $backend
	 */
	public function __construct(
		protected IAppManager $appManager,
		protected IL10N $l10n,
		protected IURLGenerator $urlGenerator,
		protected CalDavBackend $backend,
	) {
	}

	/**
	 * Get an associative array of calendars
	 * calendarId => calendar
	 *
	 * @param string $principalUri
	 * @return array
	 */
	protected function getSortedCalendars(string $principalUri): array {
		$calendars = $this->backend->getCalendarsForUser($principalUri);
		$calendarsById = [];
		foreach ($calendars as $calendar) {
			$calendarsById[(int)$calendar['id']] = $calendar;
		}

		return $calendarsById;
	}

	/**
	 * Get an associative array of subscriptions
	 * subscriptionId => subscription
	 *
	 * @param string $principalUri
	 * @return array
	 */
	protected function getSortedSubscriptions(string $principalUri): array {
		$subscriptions = $this->backend->getSubscriptionsForUser($principalUri);
		$subscriptionsById = [];
		foreach ($subscriptions as $subscription) {
			$subscriptionsById[(int)$subscription['id']] = $subscription;
		}

		return $subscriptionsById;
	}

	/**
	 * Returns the primary VEvent / VJournal / VTodo component
	 * If it's a component with recurrence-ids, it will return
	 * the primary component
	 *
	 * TODO: It would be a nice enhancement to show recurrence-exceptions
	 * as individual search-results.
	 * For now we will just display the primary element of a recurrence-set.
	 *
	 * @param string $calendarData
	 * @param string $componentName
	 * @return Component
	 */
	protected function getPrimaryComponent(string $calendarData, string $componentName, \DateTimeInterface|null $since, \DateTimeInterface|null $until): Component {
		$vCalendar = Reader::read($calendarData, Reader::OPTION_FORGIVING);

		$originalTimeZone = null;
		if ($vCalendar instanceof VCalendar && isset($since, $until)) {
			// expand() rewrites every occurrence's DTSTART/DTEND to UTC, so remember
			// the event's original timezone to display the occurrence in local time.
			$baseComponent = $vCalendar->getBaseComponent($componentName);
			if ($baseComponent !== null && isset($baseComponent->DTSTART) && $baseComponent->DTSTART->hasTime()) {
				$originalTimeZone = $baseComponent->DTSTART->getDateTime()->getTimezone();
			}

			try {
				$vCalendar = $vCalendar->expand($since, $until);
			} catch (InvalidDataException $e) {
				// fallback to the original event without expanding, leave its timezone untouched
				$originalTimeZone = null;
			}
		}

		$component = $this->selectPrimaryComponent($vCalendar->select($componentName));

		if ($originalTimeZone !== null) {
			$this->applyTimeZone($component, $originalTimeZone);
		}

		return $component;
	}

	/**
	 * @param Component[] $components
	 */
	private function selectPrimaryComponent(array $components): Component {
		// Expanded results: every instance has a RECURRENCE-ID; just take the first in-range occurrence.
		// Stored objects: a recurrence-set is the master (no RECURRENCE-ID) plus override exceptions.
		if (count($components) === 1) {
			return $components[0];
		}

		// If it's a recurrence-set, take the primary element
		foreach ($components as $component) {
			/** @var Component $component */
			if (!$component->{'RECURRENCE-ID'}) {
				return $component;
			}
		}

		// In case of error, just fallback to the first element in the set
		return $components[0];
	}

	/**
	 * Move the occurrence back into the event's original timezone after expand()
	 * has rewritten it to UTC, so the rendered time matches the user's local time.
	 */
	private function applyTimeZone(Component $component, \DateTimeZone $timeZone): void {
		foreach (['DTSTART', 'DTEND'] as $name) {
			if (isset($component->$name) && $component->$name->hasTime()) {
				$component->$name->setDateTime(
					$component->$name->getDateTime()->setTimezone($timeZone),
				);
			}
		}
	}
}
