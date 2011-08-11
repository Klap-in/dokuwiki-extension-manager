<?php
class ap_search extends ap_manage {

    var $term = NULL;
    var $filters = array();
    var $search_result = array();
    var $repo = NULL;
    var $filtered_repo = NULL;
    var $extra = NULL;
    var $versions = array();
    var $actions_list = array();
    var $search_types = array();

    function process() {
        if(empty($this->repo)) $this->refresh();
        $doku=getVersionData();
        $this->doku_version = $doku['date'];
        $this->clean_repo();
        $this->actions_list = array(
                'download'=>$this->get_lang('btn_download'),
                'disdown'=>$this->get_lang('btn_disdown')
                );
        $this->search_types = array(
            ''=>$this->get_lang('all'),
            'Syntax'=>$this->get_lang('syntax'),
            'Admin'=>$this->get_lang('admin'),
            'Action'=>$this->get_lang('action'),
            'Renderer'=>$this->get_lang('renderer'),
            'Helper'=>$this->get_lang('helper'),
            'Template'=>$this->get_lang('template')
            );
        $this->filters = array('id' => NULL,'name' => NULL,'description' => NULL, 'type' => NULL, 'tag' =>NULL, 'author' => NULL);

        if(!empty($_REQUEST['term']) > 0) {
            $this->term = $_REQUEST['term'];
            //add parsing for key=value based extras
        }
        if(!empty($_REQUEST['type'])) {
            $this->extra['type'] = $_REQUEST['type'];
        }
        if($this->term !== null || $this->extra !== null ) {
            if($this->term === null) $this->term = " ";
            if($this->repo !== null)
                $this->lookup();
        }
    }

    function html() {
        $this->html_menu();
        $this->render_search('install__search', $this->get_lang('search_plugin'),$this->term,$this->search_types);

        if(is_array($this->search_result) && count($this->search_result)) {
            $list = new plugins_list($this,'search__result',$this->actions_list);
            $list->add_header(sprintf($this->get_lang('search_results'),hsc($this->term)));
            $list->start_form();
            foreach($this->search_result as $result) {
                foreach($result as $info) {
                    $class = $this->get_class($info,'result');
                    $actions = $this->get_actions($info,'');//add some imp type info later
                    $checkbox = $this->get_checkbox($info);
                    $list->add_row($class,$info,$actions,$checkbox);
                }
            }
            $list->end_form();
            $list->render();
        } elseif(!is_null($this->term)) {
            $no_result = new plugins_list($this,'no__result');
            $no_result->add_header(sprintf($this->get_lang('not_found'),hsc($this->term)));
            $url = wl($ID,array('do'=>'admin','page'=>'plugin','tab'=>'search'));
            $no_result->add_p(sprintf($this->get_lang('no_result'),$url,$url));
            $no_result->render();
        } else {
            $full_list = new plugins_list($this,'browse__list',$this->actions_list);
            $full_list->add_header($this->get_lang('browse'));
            $full_list->start_form();
            foreach($this->filtered_repo as $info) {
                $class = $this->get_class($info,'all');
                $actions = $this->get_actions($info,'');//add some necessary type info later on
                $checkbox = $this->get_checkbox($info);
                $full_list->add_row($class,$info,$actions,$checkbox);
            }
            $full_list->end_form();
            $full_list->render();
        }
    }

    function get_class(array $info,$class) {
        if(!empty($info['securityissue'])) $class .= ' secissue';
        if(!empty($this->extra['type']) && $this->extra['type'] == "Template" )
            $class .= " template";
        return $class;
    }

    function get_actions(array $info,$type) {
        if(array_key_exists('downloadurl',$info) && !empty($info['downloadurl'])) {
            if(@stripos($info['type'],'Template')!==false) {
                $actions = $this->make_action('download',$info['id'],$this->get_lang('btn_disdown'));
            } else {
                $actions = $this->make_action('download',$info['id'],$this->get_lang('btn_download'));
                $actions .= ' | '.$this->make_action('disdown',$info['id'],$this->get_lang('btn_disdown'));
            }
        } else {
            $actions = $this->get_lang('no_url');
        }
        return $actions;
    }

    function get_checkbox($info) {
        if(!empty($info['downloadurl'])) return array();
        return array('disabled'=>'disabled');
    }

    protected function clean_repo() {
        $this->filtered_repo = array_diff_key($this->repo,array_flip($this->manager->plugin_list));
        $this->filtered_repo = array_filter($this->filtered_repo,array($this,'filter_clean'));
    }
    /**
     * Looks up the term in the repository cache according to filters set. Basic searching.
     * TODO advanced searching options (case-sensitive, for exact term etc) is it necessary??
     * TODO assign weights to matches, like id matches highest in ordering
     */
    protected function lookup() {
        foreach ($this->filtered_repo as $single) {
            $matches = array_filter($single,array($this,'search'));
            if(count($matches)) {
                $count = count(array_intersect_key($this->filters,$matches));
                if($count && $this->check($single)) {
                    if(stripos($single['id'],$this->term)) $count += 5;
                    if(stripos($single['name'],$this->term)) $count += 3;
                    $this->search_result[$count][$single['id']] = $single;
                }
            }
        }
        return krsort($this->search_result);
    }

    /**
     * Search for the term in every plugin and return matches.
     */
    protected function search($haystack) {
        if(is_array($haystack) && array_key_exists('tag',$haystack) && in_array('tag',$this->filters)) {
            return (bool) count(array_filter((array)$haystack['tag'],array($this,'search')));
        }        
        return @stripos($haystack,$this->term) !== false;
    }

    /**
     * Checks to figure out if a plugin should be searched, 
     * based on some settings, version, current context(may be?)
     */
    protected function check($plugin) {
        //$version_data = getVersionData();
        //print_r($this->extra);
        if(is_array($this->extra) && count($this->extra))
            foreach($this->extra as $index => $value)
                if(count($value)) {
                    if($index == 'type') {
                        if(strlen($value) && stripos($plugin['type'],$value) === false) return false;
                    }
                    elseif($index == 'tag') { // Tag based left here for future use
                        foreach($value as $tag)
                            if(strlen($tag))
                                if(@in_array(trim($tag),(array)$plugin['tags']['tag'])===false) return false;
                    }elseif(!(array_key_exists($index,$plugin) && $plugin[$index] == $value)) return false;
                }
        //All tests passed
        return true;
    }

    /**
     * Used to filter BEFORE the repo is searched on. only remove very very important ones
     */
    protected function filter_clean($plugin) {
        //Check for security issue
        if(!empty($plugin['securityissue'])) return false;
        if(@in_array('!obsolete',(array)$plugin['tags']['tag'])) return false;
        //all tests passed
        return true;
    } 
}
