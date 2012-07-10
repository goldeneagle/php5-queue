<?php
/**
 * Copyright 2011 Bas de Nooijer. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this listof conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDER AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are
 * those of the authors and should not be interpreted as representing official
 * policies, either expressed or implied, of the copyright holder.
 *
 * @copyright Copyright 2011 Bas de Nooijer <solarium@raspberry.nl>
 * @license http://github.com/basdenooijer/solarium/raw/master/COPYING
 * @link http://www.solarium-project.org/
 *
 * @package Solarium
 */

/**
 * Autoloader
 *
 * @package Queue
 */
class Queue_Autoloader
{
  static $modules = array();

  /**
   * Register the Queue autoloader
   *
   * Using this autoloader will disable any existing __autoload function. If
   * you want to use multiple autoloaders please use spl_autoload_register.
   *
   * @static
   * @return void
   */
  static public function register()
  {
    static::addModule("Queue", dirname(__FILE__).'/lib/');
    spl_autoload_register(array(new self, 'load'));
  }

  static public function addModule($class, $dir) {
    static::$modules[$class] = $dir;
  }

  /**
   * Autoload a class
   *
   * This method is automatically called after registering this autoloader.
   *
   * @static
   * @param string $class
   * @throws Exception
   * @return void
   */
  static public function load($class)
  {
    $package = preg_replace("/\\\\.*/", "", $class);
    $class = preg_replace("/^[^\\\\]*\\\\/", "", $class);
    if (isset(static::$modules[$package])) {
      $class = str_replace(
        array('_', '\\'),
        array('', '/'),
        $class
      );

      $file = static::$modules[$package].'/'.$class.'.php';
      require_once($file);
    } else {
      throw new Exception("Could not find $package\\$class.php");
    }
  }

}