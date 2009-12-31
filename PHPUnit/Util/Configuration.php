<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2002-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.2.0
 */

require_once 'PHPUnit/Runner/IncludePathTestCollector.php';
require_once 'PHPUnit/Util/XML.php';

PHP_CodeCoverage_Filter::getInstance()->addFileToBlacklist(__FILE__, 'PHPUNIT');

/**
 * Wrapper for the PHPUnit XML configuration file.
 *
 * Example XML configuration file:
 * <code>
 * <?xml version="1.0" encoding="utf-8" ?>
 *
 * <phpunit backupGlobals="false"
 *          backupStaticAttributes="false"
 *          bootstrap="/path/to/bootstrap.php"
 *          colors="false"
 *          convertErrorsToExceptions="true"
 *          convertNoticesToExceptions="true"
 *          convertWarningsToExceptions="true"
 *          processIsolation="false"
 *          stopOnFailure="false"
 *          testSuiteLoaderClass="PHPUnit_Runner_StandardTestSuiteLoader"
 *          verbose="false">
 *   <testsuites>
 *     <testsuite name="My Test Suite">
 *       <directory suffix="Test.php">/path/to/files</directory>
 *       <file>/path/to/MyTest.php</file>
 *     </testsuite>
 *   </testsuites>
 *
 *   <groups>
 *     <include>
 *       <group>name</group>
 *     </include>
 *     <exclude>
 *       <group>name</group>
 *     </exclude>
 *   </groups>
 *
 *   <filter>
 *     <blacklist>
 *       <directory suffix=".php">/path/to/files</directory>
 *       <file>/path/to/file</file>
 *       <exclude>
 *         <directory suffix=".php">/path/to/files</directory>
 *         <file>/path/to/file</file>
 *       </exclude>
 *     </blacklist>
 *     <whitelist addUncoveredFilesFromWhitelist="true">
 *       <directory suffix=".php">/path/to/files</directory>
 *       <file>/path/to/file</file>
 *       <exclude>
 *         <directory suffix=".php">/path/to/files</directory>
 *         <file>/path/to/file</file>
 *       </exclude>
 *     </whitelist>
 *   </filter>
 *
 *   <listeners>
 *     <listener class="MyListener" file="/optional/path/to/MyListener.php">
 *       <arguments>
 *         <array>
 *           <element key="0">
 *             <string>Sebastian</string>
 *           </element>
 *         </array>
 *         <integer>22</integer>
 *         <string>April</string>
 *         <double>19.78</double>
 *         <null/>
 *         <object class="stdClass"/>
 *       </arguments>
 *     </listener>
 *   </listeners>
 *
 *   <logging>
 *     <log type="coverage-html" target="/tmp/report" title="My Project"
            charset="UTF-8" yui="true" highlight="false"
 *          lowUpperBound="35" highLowerBound="70"/>
 *     <log type="coverage-clover" target="/tmp/clover.xml"/>
 *     <log type="json" target="/tmp/logfile.json"/>
 *     <log type="plain" target="/tmp/logfile.txt"/>
 *     <log type="tap" target="/tmp/logfile.tap"/>
 *     <log type="junit" target="/tmp/logfile.xml" logIncompleteSkipped="false"/>
 *     <log type="story-html" target="/tmp/story.html"/>
 *     <log type="story-text" target="/tmp/story.txt"/>
 *     <log type="testdox-html" target="/tmp/testdox.html"/>
 *     <log type="testdox-text" target="/tmp/testdox.txt"/>
 *   </logging>
 *
 *   <php>
 *     <ini name="foo" value="bar"/>
 *     <const name="foo" value="bar"/>
 *     <var name="foo" value="bar"/>
 *   </php>
 *
 *   <selenium>
 *     <browser name="Firefox on Linux"
 *              browser="*firefox /usr/lib/firefox/firefox-bin"
 *              host="my.linux.box"
 *              port="4444"
 *              timeout="30000"/>
 *   </selenium>
 * </phpunit>
 * </code>
 *
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2010 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 3.2.0
 */
class PHPUnit_Util_Configuration
{
    private static $instances = array();

    protected $document;
    protected $xpath;

    /**
     * Loads a PHPUnit configuration file.
     *
     * @param  string $filename
     */
    protected function __construct($filename)
    {
        $this->document = PHPUnit_Util_XML::loadFile($filename);
        $this->xpath    = new DOMXPath($this->document);
    }

    /**
     * @since  Method available since Release 3.4.0
     */
    private final function __clone()
    {
    }

    /**
     * Returns a PHPUnit configuration object.
     *
     * @param  string $filename
     * @return PHPUnit_Util_Configuration
     * @since  Method available since Release 3.4.0
     */
    public static function getInstance($filename) {
        $realpath = realpath($filename);

        if ($realpath === FALSE) {
            throw new PHPUnit_Framework_Exception(
              sprintf(
                'Could not read "%s".',
                $filename
              )
            );
        }

        if (!isset(self::$instances[$realpath])) {
            self::$instances[$realpath] = new PHPUnit_Util_Configuration($realpath);
        }

        return self::$instances[$realpath];
    }

    /**
     * Returns the configuration for SUT filtering.
     *
     * @return array
     * @since  Method available since Release 3.2.1
     */
    public function getFilterConfiguration()
    {
        $addUncoveredFilesFromWhitelist = TRUE;

        $tmp = $this->xpath->query('filter/whitelist');

        if ($tmp->length == 1 &&
            $tmp->item(0)->hasAttribute('addUncoveredFilesFromWhitelist')) {
            $addUncoveredFilesFromWhitelist = $this->getBoolean(
              (string)$tmp->item(0)->getAttribute('addUncoveredFilesFromWhitelist'),
              TRUE
            );
        }

        return array(
          'blacklist' => array(
            'include' => array(
              'directory' => $this->readFilterDirectories(
                'filter/blacklist/directory'
              ),
              'file' => $this->readFilterFiles(
                'filter/blacklist/file'
              )
            ),
            'exclude' => array(
              'directory' => $this->readFilterDirectories(
                'filter/blacklist/exclude/directory'
               ),
              'file' => $this->readFilterFiles(
                'filter/blacklist/exclude/file'
              )
            )
          ),
          'whitelist' => array(
            'addUncoveredFilesFromWhitelist' => $addUncoveredFilesFromWhitelist,
            'include' => array(
              'directory' => $this->readFilterDirectories(
                'filter/whitelist/directory'
              ),
              'file' => $this->readFilterFiles(
                'filter/whitelist/file'
              )
            ),
            'exclude' => array(
              'directory' => $this->readFilterDirectories(
                'filter/whitelist/exclude/directory'
              ),
              'file' => $this->readFilterFiles(
                'filter/whitelist/exclude/file'
              )
            )
          )
        );
    }

    /**
     * Returns the configuration for groups.
     *
     * @return array
     * @since  Method available since Release 3.2.1
     */
    public function getGroupConfiguration()
    {
        $groups = array(
          'include' => array(),
          'exclude' => array()
        );

        foreach ($this->xpath->query('groups/include/group') as $group) {
            $groups['include'][] = (string)$group->nodeValue;
        }

        foreach ($this->xpath->query('groups/exclude/group') as $group) {
            $groups['exclude'][] = (string)$group->nodeValue;
        }

        return $groups;
    }

    /**
     * Returns the configuration for listeners.
     *
     * @return array
     * @since  Method available since Release 3.4.0
     */
    public function getListenerConfiguration()
    {
        $result = array();

        foreach ($this->xpath->query('listeners/listener') as $listener) {
            $class     = (string)$listener->getAttribute('class');
            $file      = '';
            $arguments = array();

            if ($listener->hasAttribute('file')) {
                $file = (string)$listener->getAttribute('file');
            }

            if ($listener->childNodes->item(1) instanceof DOMElement &&
                $listener->childNodes->item(1)->tagName == 'arguments') {
                foreach ($listener->childNodes->item(1)->childNodes as $argument) {
                    if ($argument instanceof DOMElement) {
                        $arguments[] = PHPUnit_Util_XML::xmlToVariable($argument);
                    }
                }
            }

            $result[] = array(
              'class'     => $class,
              'file'      => $file,
              'arguments' => $arguments
            );
        }

        return $result;
    }

    /**
     * Returns the logging configuration.
     *
     * @return array
     */
    public function getLoggingConfiguration()
    {
        $result = array();

        foreach ($this->xpath->query('logging/log') as $log) {
            $type   = (string)$log->getAttribute('type');
            $target = (string)$log->getAttribute('target');

            if ($type == 'coverage-html') {
                if ($log->hasAttribute('title')) {
                    $result['title'] = (string)$log->getAttribute('title');
                }

                if ($log->hasAttribute('charset')) {
                    $result['charset'] = (string)$log->getAttribute('charset');
                }

                if ($log->hasAttribute('lowUpperBound')) {
                    $result['lowUpperBound'] = (string)$log->getAttribute('lowUpperBound');
                }

                if ($log->hasAttribute('highLowerBound')) {
                    $result['highLowerBound'] = (string)$log->getAttribute('highLowerBound');
                }

                if ($log->hasAttribute('yui')) {
                    $result['yui'] = $this->getBoolean(
                      (string)$log->getAttribute('yui'),
                      FALSE
                    );
                }

                if ($log->hasAttribute('highlight')) {
                    $result['highlight'] = $this->getBoolean(
                      (string)$log->getAttribute('highlight'),
                      FALSE
                    );
                }
            }

            else if ($type == 'junit') {
                if ($log->hasAttribute('logIncompleteSkipped')) {
                    $result['logIncompleteSkipped'] = $this->getBoolean(
                      (string)$log->getAttribute('logIncompleteSkipped'),
                      FALSE
                    );
                }
            }

            $result[$type] = $target;
        }

        return $result;
    }

    /**
     * Returns the PHP configuration.
     *
     * @return array
     * @since  Method available since Release 3.2.1
     */
    public function getPHPConfiguration()
    {
        $result = array(
          'ini'   => array(),
          'const' => array(),
          'var'   => array()
        );

        foreach ($this->xpath->query('php/ini') as $ini) {
            $name  = (string)$ini->getAttribute('name');
            $value = (string)$ini->getAttribute('value');

            $result['ini'][$name] = $value;
        }

        foreach ($this->xpath->query('php/const') as $const) {
            $name  = (string)$const->getAttribute('name');
            $value = (string)$const->getAttribute('value');

            if (strtolower($value) == 'false') {
                $value = FALSE;
            }

            else if (strtolower($value) == 'true') {
                $value = TRUE;
            }

            $result['const'][$name] = $value;
        }

        foreach ($this->xpath->query('php/var') as $var) {
            $name  = (string)$var->getAttribute('name');
            $value = (string)$var->getAttribute('value');

            if (strtolower($value) == 'false') {
                $value = FALSE;
            }

            else if (strtolower($value) == 'true') {
                $value = TRUE;
            }

            $result['var'][$name] = $value;
        }

        return $result;
    }

    /**
     * Handles the PHP configuration.
     *
     * @since  Method available since Release 3.2.20
     */
    public function handlePHPConfiguration()
    {
        $configuration = $this->getPHPConfiguration();

        foreach ($configuration['ini'] as $name => $value) {
            if (defined($value)) {
                $value = constant($value);
            }

            ini_set($name, $value);
        }

        foreach ($configuration['const'] as $name => $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        foreach ($configuration['var'] as $name => $value) {
            $GLOBALS[$name] = $value;
        }
    }

    /**
     * Returns the PHPUnit configuration.
     *
     * @return array
     * @since  Method available since Release 3.2.14
     */
    public function getPHPUnitConfiguration()
    {
        $result = array();

        if ($this->document->documentElement->hasAttribute('backupGlobals')) {
            $result['backupGlobals'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('backupGlobals'),
              FALSE
            );
        }

        if ($this->document->documentElement->hasAttribute('backupStaticAttributes')) {
            $result['backupStaticAttributes'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('backupStaticAttributes'),
              FALSE
            );
        }

        if ($this->document->documentElement->hasAttribute('bootstrap')) {
            $result['bootstrap'] = (string)$this->document->documentElement->getAttribute('bootstrap');
        }

        if ($this->document->documentElement->hasAttribute('colors')) {
            $result['colors'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('colors'),
              FALSE
            );
        }

        if ($this->document->documentElement->hasAttribute('convertErrorsToExceptions')) {
            $result['convertErrorsToExceptions'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('convertErrorsToExceptions'),
              TRUE
            );
        }

        if ($this->document->documentElement->hasAttribute('convertNoticesToExceptions')) {
            $result['convertNoticesToExceptions'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('convertNoticesToExceptions'),
              TRUE
            );
        }

        if ($this->document->documentElement->hasAttribute('convertWarningsToExceptions')) {
            $result['convertWarningsToExceptions'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('convertWarningsToExceptions'),
              TRUE
            );
        }

        if ($this->document->documentElement->hasAttribute('processIsolation')) {
            $result['processIsolation'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('processIsolation'),
              FALSE
            );
        }

        if ($this->document->documentElement->hasAttribute('stopOnFailure')) {
            $result['stopOnFailure'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('stopOnFailure'),
              FALSE
            );
        }

        if ($this->document->documentElement->hasAttribute('testSuiteLoaderClass')) {
            $result['testSuiteLoaderClass'] = (string)$this->document->documentElement->getAttribute('testSuiteLoaderClass');
        }

        if ($this->document->documentElement->hasAttribute('testSuiteLoaderFile')) {
            $result['testSuiteLoaderFile'] = (string)$this->document->documentElement->getAttribute('testSuiteLoaderFile');
        }

        if ($this->document->documentElement->hasAttribute('verbose')) {
            $result['verbose'] = $this->getBoolean(
              (string)$this->document->documentElement->getAttribute('verbose'),
              FALSE
            );
        }

        return $result;
    }

    /**
     * Returns the SeleniumTestCase browser configuration.
     *
     * @return array
     * @since  Method available since Release 3.2.9
     */
    public function getSeleniumBrowserConfiguration()
    {
        $result = array();

        foreach ($this->xpath->query('selenium/browser') as $config) {
            $name    = (string)$config->getAttribute('name');
            $browser = (string)$config->getAttribute('browser');

            if ($config->hasAttribute('host')) {
                $host = (string)$config->getAttribute('host');
            } else {
                $host = 'localhost';
            }

            if ($config->hasAttribute('port')) {
                $port = (int)$config->getAttribute('port');
            } else {
                $port = 4444;
            }

            if ($config->hasAttribute('timeout')) {
                $timeout = (int)$config->getAttribute('timeout');
            } else {
                $timeout = 30000;
            }

            $result[] = array(
              'name'    => $name,
              'browser' => $browser,
              'host'    => $host,
              'port'    => $port,
              'timeout' => $timeout
            );
        }

        return $result;
    }

    /**
     * Returns the test suite configuration.
     *
     * @return PHPUnit_Framework_TestSuite
     * @since  Method available since Release 3.2.1
     */
    public function getTestSuiteConfiguration()
    {
        $testSuiteNodes = $this->xpath->query('testsuites/testsuite');

        if ($testSuiteNodes->length == 0) {
            $testSuiteNodes = $this->xpath->query('testsuite');
        }

        if ($testSuiteNodes->length == 1) {
            return $this->getTestSuite($testSuiteNodes->item(0));
        }

        if ($testSuiteNodes->length > 1) {
            $suite = new PHPUnit_Framework_TestSuite;

            foreach ($testSuiteNodes as $testSuiteNode) {
                $suite->addTestSuite($this->getTestSuite($testSuiteNode));
            }

            return $suite;
        }
    }

    /**
     * @param  DOMElement $testSuiteNode
     * @return PHPUnit_Framework_TestSuite
     * @since  Method available since Release 3.4.0
     */
    protected function getTestSuite(DOMElement $testSuiteNode)
    {
        if ($testSuiteNode->hasAttribute('name')) {
            $suite = new PHPUnit_Framework_TestSuite(
              (string)$testSuiteNode->getAttribute('name')
            );
        } else {
            $suite = new PHPUnit_Framework_TestSuite;
        }

        foreach ($testSuiteNode->getElementsByTagName('directory') as $directoryNode) {
            if ($directoryNode->hasAttribute('prefix')) {
                $prefix = (string)$directoryNode->getAttribute('prefix');
            } else {
                $prefix = '';
            }

            if ($directoryNode->hasAttribute('suffix')) {
                $suffix = (string)$directoryNode->getAttribute('suffix');
            } else {
                $suffix = 'Test.php';
            }

            $testCollector = new PHPUnit_Runner_IncludePathTestCollector(
              array((string)$directoryNode->nodeValue), $suffix, $prefix
            );

            $suite->addTestFiles($testCollector->collectTests());
        }

        foreach ($testSuiteNode->getElementsByTagName('file') as $fileNode) {
            $suite->addTestFile((string)$fileNode->nodeValue);
        }

        return $suite;
    }

    /**
     * @param  string  $value
     * @param  boolean $default
     * @return boolean
     * @since  Method available since Release 3.2.3
     */
    protected function getBoolean($value, $default)
    {
        if (strtolower($value) == 'false') {
            return FALSE;
        }

        else if (strtolower($value) == 'true') {
            return TRUE;
        }

        return $default;
    }

    /**
     * @param  string $query
     * @return array
     * @since  Method available since Release 3.2.3
     */
    protected function readFilterDirectories($query)
    {
        $directories = array();

        foreach ($this->xpath->query($query) as $directory) {
            if ($directory->hasAttribute('prefix')) {
                $prefix = (string)$directory->getAttribute('prefix');
            } else {
                $prefix = '';
            }

            if ($directory->hasAttribute('suffix')) {
                $suffix = (string)$directory->getAttribute('suffix');
            } else {
                $suffix = '.php';
            }

            if ($directory->hasAttribute('group')) {
                $group = (string)$directory->getAttribute('group');
            } else {
                $group = 'DEFAULT';
            }

            $directories[] = array(
              'path'   => (string)$directory->nodeValue,
              'prefix' => $prefix,
              'suffix' => $suffix,
              'group'  => $group
            );
        }

        return $directories;
    }

    /**
     * @param  string $query
     * @return array
     * @since  Method available since Release 3.2.3
     */
    protected function readFilterFiles($query)
    {
        $files = array();

        foreach ($this->xpath->query($query) as $file) {
            $files[] = (string)$file->nodeValue;
        }

        return $files;
    }
}
?>
