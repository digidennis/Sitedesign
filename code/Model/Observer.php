<?php

class Digidennis_SiteDesign_Model_Observer {

    public function refreshbycronCache() {
        try {
            $allTypes = Mage::app()->useCache();
            foreach($allTypes as $type => $blah) {
                Mage::app()->getCacheInstance()->cleanType($type);
            }
        } catch (Exception $e) {
            // do something
            error_log($e->getMessage());
        }
    }

}