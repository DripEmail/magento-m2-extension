<?php
namespace Drip\Connect\Api;
interface ResponseInterface {
    /**
     * Get order url
     *
     * @return string
     */
     public function getOrderUrl();

    /**
     * Get product url
     *
     * @return string
     */
     public function getProductUrl();

    /**
     * Get image url
     *
     * @return string
     */
     public function getImageUrl();

      /**
       * Get account param
       *
       * @return string
       */
       public function getAccountParam();

       /**
        * Get account param
        *
        * @return string
        */
        public function getIntegrationToken();

        /**
         * Get plugin version
         *
         * @return string
         */
         public function getPluginVersion();

         /**
          * Get magento version
          *
          * @return string
          */
          public function getMagentoVersion();
}
