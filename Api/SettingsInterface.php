<?php
namespace Drip\Connect\Api;
interface SettingsInterface {
		/**
		 * GET for Settings api
		 * @param string $websiteId
		 * @return string
		 */
    public function getAccountId($websiteId);
}
