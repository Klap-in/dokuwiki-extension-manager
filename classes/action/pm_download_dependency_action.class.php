<?php
class pm_download_dependency_action extends pm_download_action {
    function down() {
        if(is_array($this->plugin)) {
            foreach($this->plugin as $plugin) {
                $info = $this->manager->info->get($plugin,'search');
                if($info->can_download_dependency()) {
                    //get $info->missing_dependency, add it to current list and use download
                }
            }
        }
    }
}
