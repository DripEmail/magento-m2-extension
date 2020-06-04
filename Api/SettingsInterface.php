<?php
namespace Drip\Connect\Api;
interface SettingsInterface {
    /**
     * POST for integration settings API
     * @param string $websiteId
     * @param string $accountParam
     * @param string $integrationToken
     * @return \Drip\Connect\Api\Response
     */
     public function updateSettings($websiteId = 0, $accountParam, $integrationToken);

    /**
     * GET for integration settings API
     * @param string $websiteId]
     * @return \Drip\Connect\Api\Response
     */
    public function showStatus($websiteId = 0);
}
