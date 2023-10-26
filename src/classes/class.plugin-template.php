<?php
declare(strict_types=1);
namespace YeAPF\Plugins;
/**
 * ServicePluginInterface is the basic interface for all plugin classes and objects
 */
interface ServicePluginInterface
{
    public function registerServiceMethods($server);
}


/**
 * NOTA
 * 20230428
 * Revisar https://www.phptutorial.net/php-oop/php-__call/
 */

/**
 * DummyPlugin serves as shock absorber.
 *
 * This class pretends to be used instead of null in
 * some control functions.
 * That helps to build a more clean code as we can just
 * be confident on the control function return.
 * It always return true when asked if it is a dummy object.
 */
class DummyPlugin extends \YeAPF\KeyData {

  /**
   * It always return true when asked if it is a dummy object.
   *
   * @return true
   */
  public function is_dummy() {
    return (bool) true;
  }
}


/**
 * ServicePlugin is the basis for all plugins.
 *
 * The main function is to self register as a plugin
 * object against the $pluginList object
 */
class ServicePlugin extends \YeAPF\KeyData {

    /**
     * Object constructor.
     *
     * Declare itself as a plugin object indicating where is the
     * original file of the implementation.
     * That helps to create the proxy functions for nuSOAP.
     *
     * @param string $filename
     */
    function __construct(string $filename) {
        \YeAPF\Plugins\PluginList::registerPlugin($this, $filename);
    }

    /**
     * It always return false when asked if it is a dummy object.
     *
     * @return false
     */
    public function is_dummy() {
        return false;
    }
}

global $dummyPlugin;
$dummyPlugin = new DummyPlugin;
