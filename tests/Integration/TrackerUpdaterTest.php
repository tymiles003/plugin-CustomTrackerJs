<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link    http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTrackerJs\tests\Integration;

use Piwik\EventDispatcher;
use Piwik\Plugins\CustomTrackerJs\TrackerUpdater;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Plugins\CustomTrackerJs\Additions\Extension;

/**
 * @group CustomTrackerJs
 * @group Plugins
 */
class TrackerUpdaterTest extends IntegrationTestCase
{
    private $file = '/Fixture/tmp.js';
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function setUp()
    {
        parent::setUp();

        $this->file = __DIR__ . '/Fixture/tmp.js';
        $this->eventDispatcher = EventDispatcher::getInstance();

        // Prepare a file that we can manipulate for the tests
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        copy(__DIR__ . '/Fixture/test.js', $this->file);
    }

    public function tearDown()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    public function testUpdateTracker()
    {
        $this->eventDispatcher->addObserver('CustomTrackerJs.getTrackerJsExtension', function (Extension $extension) {
            $extension->setTopCode('var foo;');
            $extension->setBottomCode('var foo;');
        });


        $updater = new TrackerUpdater($this->file);
        $updater();

        $expected = <<<JS

/* GENERATED: plugin additions */
var foo;
/* END GENERATED: plugin additions */
// Hello world
/* GENERATED: plugin additions */
var foo;
/* END GENERATED: plugin additions */

JS;

        $this->assertEquals($expected, file_get_contents($this->file));

        // Also test that further updates will not change the file
        $updater();
        $this->assertEquals($expected, file_get_contents($this->file));
    }

    public function testMultipleEventListeners()
    {
        $this->eventDispatcher->addObserver('CustomTrackerJs.getTrackerJsExtension', function (Extension $extension) {
            $extension->setTopCode('var foo;');
            $extension->setBottomCode('var foo;');
        });

        $updater = new TrackerUpdater($this->file);
        $updater();

        $expected = <<<JS

/* GENERATED: plugin additions */
var foo;
/* END GENERATED: plugin additions */
// Hello world
/* GENERATED: plugin additions */
var foo;
/* END GENERATED: plugin additions */

JS;
        $this->assertEquals($expected, file_get_contents($this->file));
    }

    /**
     * @expectedException Piwik\Plugins\CustomTrackerJs\Exception\AccessDeniedException
     * @expectedExceptionMessage You have no access to piwik.js file
     */
    public function testUnknownFile()
    {
        $updater = new TrackerUpdater('foobar');
        $updater();
    }
}
