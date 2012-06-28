<?php
/**
 * Extension management functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Christopher Smith <chris@jalakai.co.uk>
 * @author     Piyush Mishra <me@piyushmishra.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_extension extends DokuWiki_Admin_Plugin {

    var $hlp = null;

    /**
     * Array of extensions sent by POST method
     */
    var $selection = NULL;

    /**
     * The action to be carried out
     * one from either admin_plugin_extension::$functions or admin_plugin_extension::$commands
     */
    var $cmd = 'display';

    /**
     * The current tab which is being shown
     * one from admin_plugin_extension::$nav_tabs
     */
    var $tab = 'plugin';

    /**
     * Instance of the tab from admin_plugin_extension::$tab
     */
    var $handler = NULL;

    /**
     * If a plugin has info clicked, its "id"
     * @see pm_base_single_lib::$id
     */
    var $showinfo = null;

    /**
     * list of valid actions(classes/action/*.class.php)
     */
    var $valid_actions = array('delete','enable','update','disable','reinstall','info','search','download','download_disabled','repo_reload');

    /**
     * array of navigation tab ids
     */
    var $nav_tabs = array('plugin', 'template', 'search');

    function __construct() {
        $this->hlp =& plugin_load('helper', 'extension');
        if(!$this->hlp) msg('Loading the extension manager helper failed.',-1);
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 20;
    }

    /**
     * handle user request
     */
    function handle() {
        $this->hlp->init();

        if(isset($_REQUEST['info']))
            $this->showinfo = $_REQUEST['info'];
        //Setup the selected tab
        if(!empty($_REQUEST['tab']) && in_array($_REQUEST['tab'],$this->nav_tabs)) {
            $this->tab = $_REQUEST['tab'];
        } else {
            $this->tab = 'plugin';
        }
        //setup and carry out the action requested
        $this->setup_action();
        $this->handler = $this->instantiate($this->tab,'tab');
        if(is_null($this->handler)) $this->handler = new pm_plugin_tab($this);
        $this->handler->process();
    }

    /**
     * Determines which action has been requested and executes the action
     * stores name of action in admin_plugin_extension::$cmd and the 
     * instance of action in admin_plugin_extension::$action 
     */
    function setup_action() {
        $fn = $_REQUEST['fn'];
        if (is_array($fn)) {
            $this->cmd = key($fn);
            $extension = current($fn);
            if (is_array($extension)) {
                $this->selection = array_keys($extension);
            }
        } else {
            $this->cmd = $fn;
        }
        if(!empty($_REQUEST['checked'])) {
            $this->selection = $_REQUEST['checked'];
        }
        // verify $_REQUEST vars and check for security token
        if ($this->valid_request()) {
            $this->action = $this->instantiate($this->cmd,'action');
        }
    }

    /**
     * @param string name of the class to be instantiated
     * @param string type (classes/<foldername>) of the class
     * @return mixed object/null
     */
    function instantiate($name,$type) {
        $class = 'pm_'.$name."_".$type;
        if(class_exists($class))
            return new $class($this);
        return null;
    }

    /**
     * validate the request
     * @return bool if the requested action should be carried out or not
     */
    function valid_request() {
        //if command is empty, we need to make it
        if(empty($this->cmd)) return false;
        if(in_array($this->cmd, $this->valid_actions) && checkSecurityToken()) return true;
        return false;
    }

    /**
     * output appropriate html
     */
    function html() {

        if (is_null($this->handler)) {
            $this->hlp->get_plugin_list();
            $this->handler = new pm_plugin_tab($this);
            $this->handler->process();
        }

        ptln('<div id="extension__manager">');
        print $this->locale_xhtml('extension_intro');
        ptln('<div class="panel">');
        $this->handler->html();
        ptln('</div><!-- panel -->');
        ptln('</div><!-- #extension__manager -->');
    }

    function getTOC() {
        if ($this->tab != 'plugin') return array();

        $toc = array();
        $toc[] = html_mktocitem('extension_manager', $this->getLang('menu'), 1);
        $toc[] = html_mktocitem('installed_plugins', $this->getLang('header_plugin_installed'), 2);
        $toc[] = html_mktocitem('protected_plugins', $this->getLang('header_plugin_protected'), 2);
        return $toc;
    }

}
