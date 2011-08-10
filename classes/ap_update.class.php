<?php
require_once(DOKU_PLUGIN."plugin/classes/ap_download.class.php");
class ap_update extends ap_download {

    var $overwrite = true;

    function down() {
        $this->type = !empty($_REQUEST['template'])? 'template': 'plugin';
        $base_path = ($this->type == "template")? DOKU_INC.'lib/tpl/' : DOKU_PLUGIN;
        foreach($this->plugin as $plugin) {
            if(in_array($plugin,$this->_bundled)) continue;
            $this->current = null;
            $this->manager->error = null;
            $info = $this->_info_list($plugin,$type);
            $default_base = $info['base'];
            if(@file_exists($base_path.$plugin.'/manager.dat')) {
                $plugin_url = $this->fetch_log($base_path.$plugin.'/', 'downloadurl');
                if(!empty($plugin_url)) {
                    if($this->download($plugin_url, $this->overwrite,$default_base,$this->type,$info)) {
                        $base = $this->current['base'];
                        if($this->type == 'template') {
                            msg(sprintf($this->get_lang('tempupdated'),$base),1);
                        } else {
                            msg(sprintf($this->get_lang('updated'),$base),1);
                        }
                    } else {
                        msg("<strong>".$plugin.":</strong> ".$this->get_lang('update_error')."<br />".$this->manager->error,-1);
                    }
                 } else {
                    msg("<strong>".$plugin.":</strong> ".$this->get_lang('update_error')."<br />".$this->manager->error,-1);
                 }
            } else {
                msg("<strong>".$plugin.":</strong> ".$this->get_lang('update_error')."<br />".$this->get_lang('no_manager'),-1);
            }
            
        }
    }
}

