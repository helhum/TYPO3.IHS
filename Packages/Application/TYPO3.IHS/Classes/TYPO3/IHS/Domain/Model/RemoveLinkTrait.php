<?php
namespace TYPO3\IHS\Domain\Model;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.IHS".             *
 *                                                                        *
 *                                                                        */

/**
 * Trait RemoveLinkTrait
 *
 * @package TYPO3\IHS\Domain\Model
 */
trait RemoveLinkTrait {

	/**
	 * Thx to Carsten Bleicker for this workaround
	 * @see https://forge.typo3.org/issues/54046
	 *
	 * @param Link $link
	 * @return $this
	 */
	public function removeLink(Link $link) {
		$filter = function (Link $existingLink) use ($link) {
			// @todo At this point you could use persistenceManager to check for equal identities
			return ($existingLink->getUri() === $link->geturi() && $existingLink->getTitle() === $link->getTitle() && $existingLink->getDescription() === $link->getDescription());
		};

		$existingLinkToRemove = $this->links->filter($filter);

		while ($existingLinkToRemove->current()) {
			$this->links->removeElement($existingLinkToRemove->current());
			$existingLinkToRemove->next();
		}

		return $this;
	}
}