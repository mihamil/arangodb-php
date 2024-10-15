<?php
/**
 * ArangoDB PHP client testsuite
 * File: AnalyzerTest.php
 *
 * @package ArangoDBClient
 * @author  Jan Steemann
 */

namespace ArangoDBClient;

/**
 * Class AnalyzerTest
 * Basic Tests for the analyzer API implementation
 *
 * @property Connection        $connection
 *
 * @package ArangoDBClient
 */
class AnalyzerTest extends
    EvocaTestParent
{
    protected static $testsTimestamp;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        static::$testsTimestamp = str_replace('.', '_', (string) microtime(true));
    }


    public function setUp(): void
    {
        $this->connection  = getConnection();
        $this->analyzerHandler = new AnalyzerHandler($this->connection);
    }
    
    /**
     * Test creation of analyzer
     */
    public function testCreateAnalyzerObject()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $analyzer->getName());
        static::assertEquals('text', $analyzer->getType());
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }

    /**
     * Test creation of identity analyzer
     */
    public function testCreateIdentityAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('identity', $result['type']);
        static::assertEquals([],$analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of text analyzer
     */
    public function testCreateTextAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('text', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of text analyzer
     */
    public function testCreateTextAnalyzerFail()
    {
        try {
            $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text');
            $this->analyzerHandler->create($analyzer);
        } catch (\Exception $exception) {
        }
        static::assertEquals(400, $exception->getCode());
    }
    
    
    /**
     * Test creation of segmentation analyzer
     */
    public function testCreateSegmentationAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'segmentation', [ "break" => "alpha" ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('segmentation', $result['type']);
        static::assertEquals('alpha', $result['properties']['break']);
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    
    /**
     * Test creation of collation analyzer
     */
    public function testCreateCollationAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'collation', [ "locale" => "en.utf-8" ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('collation', $result['type']);
        static::assertEquals('en.utf-8', $result['properties']['locale']);
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    
    /**
     * Test creation of geopoint analyzer
     */
    public function testCreateGeoPointAnalyzer()
    {
        $options = [ ]; //"maxCells" => 20, "minLevel" => 4, "maxLevel" => 40 ];
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'geopoint', [ "latitude" => ["lat"], "longitude" => ["lng"] ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('geopoint', $result['type']);
        static::assertEquals(['lat'], $result['properties']['latitude']);
        static::assertEquals(['lng'], $result['properties']['longitude']);
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of geojson analyzer
     */
    public function testCreateGeoJsonAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'geojson', [ "type" => "point" ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('geojson', $result['type']);
        static::assertEquals('point', $result['properties']['type']);
        static::assertEquals([], $analyzer->getFeatures());
    }

    /**
     * Test creation of stopwords analyzer
     */
    public function testCreateStopwordsAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'stopwords', [ "stopwords" => ["foo", "bar", "baz", "dead"] ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('stopwords', $result['type']);
        static::assertEquals([ "stopwords" => ["foo", "bar", "baz", "dead"] ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of delimiter analyzer
     */
    public function testCreateDelimiterAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'delimiter', [ "delimiter" => " " ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('delimiter', $result['type']);
        static::assertEquals([ "delimiter" => " " ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of norm analyzer
     */
    public function testCreateNormAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'norm', [ "locale" => "en.UTF-8", "accent" => false, "case" => "lower" ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('norm', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "accent" => false, "case" => "lower" ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test creation of pipeline analyzer
     */
    public function testCreatePipelineAnalyzer()
    {
        $data = [ "pipeline" => [
            [ "type" => "delimiter", "properties" => [ "delimiter" => " " ] ],
            [ "type" => "norm", "properties" => [ "locale" => "en.UTF-8", "accent" => false, "case" => "lower" ] ],
            [ "type" => "stopwords", "properties" => [ "stopwords" => ["foo", "bar", "baz", "dead"] ] ]
        ] ];

        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'pipeline', $data);
        $result = $this->analyzerHandler->create($analyzer);

        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('pipeline', $result['type']);
        static::assertEquals($data, $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test getting an analyzer
     */
    public function testGetAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $this->analyzerHandler->create($analyzer);

        $result = $this->analyzerHandler->get('Analyzer1' . '_' . static::$testsTimestamp);
        static::assertEquals('_system::Analyzer1' . '_' . static::$testsTimestamp, $result->getName());
        static::assertEquals('text', $result->getType());
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test getting default analyzers
     */
    public function testGetDefaultAnalyzers()
    {
        $result = $this->analyzerHandler->getAll();
        static::assertFalse($result['error']);

        $analyzers = $result['result'];
        static::assertTrue(count($analyzers) > 0);

        $found = [];
        foreach ($analyzers as $analyzer) {
          $name = $analyzer['name'];
          $found[] = $name;
        }

        static::assertTrue(in_array('text_fi', $found));
        static::assertTrue(in_array('text_ru', $found));
        static::assertTrue(in_array('text_de', $found));
        static::assertTrue(in_array('text_en', $found));
        static::assertTrue(in_array('text_pt', $found));
        static::assertTrue(in_array('text_nl', $found));
        static::assertTrue(in_array('text_fr', $found));
        static::assertTrue(in_array('text_zh', $found));
    }
    
    /**
     * Test getting all analyzers
     */
    public function testGetAllAnalyzers()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $this->analyzerHandler->create($analyzer);
        
        $analyzer = new Analyzer('Analyzer2' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $this->analyzerHandler->create($analyzer);

        $result = $this->analyzerHandler->getAll();
        static::assertFalse($result['error']);

        $analyzers = $result['result'];
        static::assertTrue(count($analyzers) > 0);

        $found = [];
        foreach ($analyzers as $analyzer) {
          $name = $analyzer['name'];
          $found[] = $name;
        }

        static::assertTrue(in_array('_system::Analyzer1' . '_' . static::$testsTimestamp, $found));
        static::assertTrue(in_array('_system::Analyzer2' . '_' . static::$testsTimestamp, $found));
    }
    
    /**
     * Test getting a non-existing analyzer
     */
    public function testGetNonExistingAnalyzer()
    {
        try {
            $this->analyzerHandler->get('Analyzer1' . '_' . static::$testsTimestamp);
        } catch (\Exception $exception) {
        }
        static::assertEquals(404, $exception->getCode());
    }
    
    /**
     * Test analyzer properties
     */
    public function testAnalyzerProperties()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('identity', $result['type']);
        static::assertEquals([], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());

        $result = $this->analyzerHandler->properties($analyzer);
        static::assertEquals('_system::Analyzer1' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('identity', $result['type']);
        static::assertEquals([], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
        
        $analyzer = new Analyzer('Analyzer2' . '_' . static::$testsTimestamp, 'text', [ "locale" => "en.UTF-8", "stopwords" => [] ]);
        $result = $this->analyzerHandler->create($analyzer);
        static::assertEquals('Analyzer2' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('text', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
        
        $result = $this->analyzerHandler->properties($analyzer);
        static::assertEquals('_system::Analyzer2' . '_' . static::$testsTimestamp, $result['name']);
        static::assertEquals('text', $result['type']);
        static::assertEquals([ "locale" => "en.UTF-8", "stopwords" => [] ], $analyzer->getProperties());
        static::assertEquals([], $analyzer->getFeatures());
    }
    
    /**
     * Test drop analyzer
     */
    public function testDropAnalyzer()
    {
        $analyzer = new Analyzer('Analyzer1' . '_' . static::$testsTimestamp, 'identity');
        $this->analyzerHandler->create($analyzer);
        $result = $this->analyzerHandler->drop('Analyzer1' . '_' . static::$testsTimestamp);
        static::assertTrue($result);
    }
    
    /**
     * Test drop non-existing analyzer
     */
    public function testDropNonExistingAnalyzer()
    {
        try {
            $this->analyzerHandler->drop('Analyzer1' . '_' . static::$testsTimestamp);
        } catch (\Exception $exception) {
        }
        static::assertEquals(404, $exception->getCode());
    }
    
    public function tearDown(): void
    {
        $this->analyzerHandler = new AnalyzerHandler($this->connection);
        try {
            $this->analyzerHandler->drop('Analyzer1' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
        try {
            $this->analyzerHandler->drop('Analyzer2' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
    }
}
