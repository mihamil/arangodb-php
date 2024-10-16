<?php
/**
 * ArangoDB PHP client testsuite
 * File: ConnectionTest.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

/**
 * Class ConnectionTest
 *
 * @property Connection        $connection
 * @property Collection        $collection
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
 *
 * @package ArangoDBClient
 */
class ConnectionTest extends
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
        $this->connection        = getConnection();
        $this->collectionHandler = new CollectionHandler($this->connection);

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            //Silence the exception
        }
    }
    
    /**
     * Test if Connection instance can be initialized
     */
    public function testInitializeConnection()
    {
        $connection = getConnection();
        static::assertInstanceOf(Connection::class, $connection);
    }

    /**
     * Test if Connection works
     */
    public function testTestUnconnected()
    {
        $connection = new Connection([
            ConnectionOptions::OPTION_ENDPOINT => 'tcp://1.1.1.1:9999',
            ConnectionOptions::OPTION_TIMEOUT => 1
        ]);
        static::assertEquals('tcp://1.1.1.1:9999', $connection->getCurrentEndpoint());
        static::assertFalse($connection->test());
        
        $connection = new Connection([
            ConnectionOptions::OPTION_ENDPOINT => 'ssl://1.1.1.1:9999',
            ConnectionOptions::OPTION_TIMEOUT => 1
        ]);
        static::assertEquals('ssl://1.1.1.1:9999', $connection->getCurrentEndpoint());
        static::assertFalse($connection->test());
    }
    
    
    /**
     * Test if Connection works
     */
    public function testTest()
    {
        $connection = getConnection();
        $ep = $connection->getOption(ConnectionOptions::OPTION_ENDPOINT)[0];
        static::assertEquals($ep, $connection->getCurrentEndpoint());
        
        // test the connection
        static::assertTrue($connection->test());
        // endpoint should not change
        static::assertEquals($ep, $connection->getCurrentEndpoint());
    }

    
    /**
     * Test endpoint and port
     */
    public function testEndpointAndPort()
    {
        $options = [ ConnectionOptions::OPTION_ENDPOINT => 'tcp://127.0.0.10:9242' ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://127.0.0.10:9242' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(9242, $co[ConnectionOptions::OPTION_PORT]);
        
        $options = [ ConnectionOptions::OPTION_ENDPOINT => 'tcp://192.168.9.9:433' ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://192.168.9.9:433' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(433, $co[ConnectionOptions::OPTION_PORT]);
        
        $options = [ ConnectionOptions::OPTION_ENDPOINT => 'tcp://myserver.example.com:432' ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://myserver.example.com:432' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(432, $co[ConnectionOptions::OPTION_PORT]);
        
        $options = [ ConnectionOptions::OPTION_ENDPOINT => [ 'tcp://master:8529' ] ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://master:8529' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(8529, $co[ConnectionOptions::OPTION_PORT]);
        
        $options = [ ConnectionOptions::OPTION_ENDPOINT => [ 'tcp://master:1234' ] ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://master:1234' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(1234, $co[ConnectionOptions::OPTION_PORT]);
        
        $options = [ ConnectionOptions::OPTION_ENDPOINT => [ 'tcp://master:8529', 'tcp://slave:1235' ] ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://master:8529', 'tcp://slave:1235' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(8529, $co[ConnectionOptions::OPTION_PORT]);
        
        $options = [ ConnectionOptions::OPTION_ENDPOINT => [ 'tcp://master:8529', 'tcp://slave:8529', 'tcp://blackhole:8529' ] ];
        $co   = new ConnectionOptions($options);
        static::assertEquals([ 'tcp://master:8529', 'tcp://slave:8529', 'tcp://blackhole:8529' ], $co[ConnectionOptions::OPTION_ENDPOINT]);
        static::assertEquals(8529, $co[ConnectionOptions::OPTION_PORT]);
       
        $excepted = false;
        try {
            $options = [ ConnectionOptions::OPTION_ENDPOINT => 'tcp://server.com' ];
            $co   = new ConnectionOptions($options);
        } catch (\Exception $exception) {
            $excepted = true;
        }

        static::assertTrue($excepted);
    }


    /**
     * This is just a test to really test connectivity with the server before moving on to further tests.
     */
    public function testGetStatus()
    {
        $connection = getConnection();
        $response   = $connection->get('/_admin/statistics');
        static::assertEquals(200, $response->getHttpCode(), 'Did not return http code 200');
    }
    
    /**
     * Test get options
     */
    public function testGetOptions()
    {
        $connection = getConnection();
        
        $old = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 12);
        $value = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, $old);
        static::assertEquals(12, $value);

        $value = $connection->getOption(ConnectionOptions::OPTION_CONNECTION);
        static::assertEquals(getenv('ArangoDB-PHP-Connection'), $value);

        $value = $connection->getOption(ConnectionOptions::OPTION_RECONNECT);
        static::assertFalse($value);

        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        static::assertEquals('_system', $value);

        $value = $connection->getOption(ConnectionOptions::OPTION_VERIFY_CERT);
        static::assertFalse($value);

        $value = $connection->getOption(ConnectionOptions::OPTION_ALLOW_SELF_SIGNED);
        static::assertTrue($value);
    }

    /**
     * Test set options
     */
    public function testSetOptions()
    {
        $connection = getConnection();

        // timeout
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 10);
        $value = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        static::assertEquals(10, $value);

        // connection
        $connection->setOption(ConnectionOptions::OPTION_CONNECTION, 'Keep-Alive');
        $value = $connection->getOption(ConnectionOptions::OPTION_CONNECTION);
        static::assertEquals('Keep-Alive', $value);

        // reconnect 
        $connection->setOption(ConnectionOptions::OPTION_RECONNECT, true);
        $value = $connection->getOption(ConnectionOptions::OPTION_RECONNECT);
        static::assertTrue($value);

        $connection->setOption(ConnectionOptions::OPTION_RECONNECT, false);
        $value = $connection->getOption(ConnectionOptions::OPTION_RECONNECT);
        static::assertFalse($value);
    }
    
    /**
     * Test timeout options handling
     */
    public function testTimeoutOptions()
    {
        $connection = getConnection();
        
        $oldTimeout = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $oldConnectTimeout = $connection->getOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT);
        $oldRequestTimeout = $connection->getOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT);

        static::assertEquals($oldTimeout, $oldConnectTimeout);
        static::assertEquals($oldTimeout, $oldRequestTimeout);

        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 12);
        $newTimeout = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $newConnectTimeout = $connection->getOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT);
        $newRequestTimeout = $connection->getOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT);
        
        static::assertEquals(12, $newTimeout);
        static::assertEquals(12, $newConnectTimeout);
        static::assertEquals(12, $newRequestTimeout);
        
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 42);
        $newTimeout = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $newConnectTimeout = $connection->getOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT);
        $newRequestTimeout = $connection->getOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT);
        
        static::assertEquals(42, $newTimeout);
        static::assertEquals(42, $newConnectTimeout);
        static::assertEquals(42, $newRequestTimeout);

        $connection->setOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT, 1.5);
        try {
            $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
            static::assertFalse(true);
        } catch (\Exception $e) {
            // OPTION_TIMEOUT is gone once OPTION_CONNECT_TIMEOUT is used
        }

        $newConnectTimeout = $connection->getOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT);
        $newRequestTimeout = $connection->getOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT);
        
        static::assertEquals(1.5, $newConnectTimeout);
        static::assertEquals(42, $newRequestTimeout);
        
        $connection->setOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT, 24.5);
        $newConnectTimeout = $connection->getOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT);
        $newRequestTimeout = $connection->getOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT);
        
        try {
            $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
            static::assertFalse(true);
        } catch (\Exception $e) {
            // OPTION_TIMEOUT is gone once OPTION_REQUEST_TIMEOUT is used
        }
        
        static::assertEquals(1.5, $newConnectTimeout);
        static::assertEquals(24.5, $newRequestTimeout);
        
        
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 8);
        $newTimeout = $connection->getOption(ConnectionOptions::OPTION_TIMEOUT);
        $newConnectTimeout = $connection->getOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT);
        $newRequestTimeout = $connection->getOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT);
        
        static::assertEquals(8, $newTimeout);
        static::assertEquals(8, $newConnectTimeout);
        static::assertEquals(8, $newRequestTimeout);
    }


    /**
     * Test set invalid options
     */
    public function testSetEndpointOption()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_ENDPOINT, 'tcp://127.0.0.1:8529');
    }

    /**
     * Test set invalid options
     */
    public function testSetAllowSelfSignedOption()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_ALLOW_SELF_SIGNED, true);
    }

    /**
     * Test set invalid options
     */
    public function testSetVerifyCert()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_VERIFY_CERT, true);
    }

    /**
     * Test set invalid options
     */
    public function testSetCiphers()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_CIPHERS, 'ALL');
    }

    /**
     * Test set invalid options
     */
    public function testSetHostOption()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_HOST, '127.0.0.1');
    }

    /**
     * Test set invalid options
     */
    public function testSetPortOption()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();

        // will fail!
        $connection->setOption(ConnectionOptions::OPTION_PORT, '127.0.0.1');
    }

    /**
     * Test get/set database
     */
    public function testGetSetDatabase()
    {
        $connection = getConnection();

        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        static::assertEquals('_system', $value);

        $value = $connection->getDatabase();
        static::assertEquals('_system', $value);

        // set the database to something else and re-check
        $connection->setDatabase('foobar');

        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        static::assertEquals('foobar', $value);

        $value = $connection->getDatabase();
        static::assertEquals('foobar', $value);

        // set the database back and re-check
        $connection->setOption(ConnectionOptions::OPTION_DATABASE, '_system');

        $value = $connection->getOption(ConnectionOptions::OPTION_DATABASE);
        static::assertEquals('_system', $value);

        $value = $connection->getDatabase();
        static::assertEquals('_system', $value);
    }

    /**
     * Test timeout exception
     */
    public function testSetTimeoutException()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 3);
        $query = 'RETURN SLEEP(6)';

        $statement = new Statement($connection, ['query' => $query]);

        try {
            // this is expected to fail
            $statement->execute();
        } catch (ClientException $exception) {
            static::assertEquals(408, $exception->getCode());
            throw $exception;
        }
    }
    
    /**
     * Test timeout, no exception
     */
    public function testSetTimeout()
    {
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_TIMEOUT, 5);
        $query = 'RETURN SLEEP(1)';

        $statement = new Statement($connection, ['query' => $query]);

        // should work
        $cursor = $statement->execute();
        static::assertCount(1, $cursor->getAll());
    }
    
    /**
     * Test connect timeout, no exception
     */
    public function testSetConnectTimeout()
    {
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT, 5);
        $query = 'RETURN SLEEP(1)';

        $statement = new Statement($connection, ['query' => $query]);

        // should work
        $cursor = $statement->execute();
        static::assertCount(1, $cursor->getAll());
    }
    
    /**
     * Test request timeout exception
     */
    public function testSetRequestTimeoutException()
    {
        $this->expectException(\ArangoDBClient\ClientException::class);
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT, 3);
        $connection->setOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT, 2);
        $query = 'RETURN SLEEP(3)';

        $statement = new Statement($connection, ['query' => $query]);

        try {
            // this is expected to fail
            $statement->execute();
        } catch (ClientException $exception) {
            static::assertEquals(408, $exception->getCode());
            throw $exception;
        }
    }
    
    /**
     * Test request timeout, no exception
     */
    public function testSetRequestTimeout()
    {
        $connection = getConnection();
        $connection->setOption(ConnectionOptions::OPTION_CONNECT_TIMEOUT, 5);
        $connection->setOption(ConnectionOptions::OPTION_REQUEST_TIMEOUT, 5);
        $query = 'RETURN SLEEP(1)';

        $statement = new Statement($connection, ['query' => $query]);

        // should work
        $cursor = $statement->execute();
        static::assertCount(1, $cursor->getAll());
    }

    /**
     * Test "connection: close"
     */
    public function testConnectionClose()
    {
        $done   = false;
        $tracer = function ($type, $data) use (&$done) {
            if ($type === 'send') {
                static::assertNotFalse(stripos($data, 'Connection: Close'));
                $done = true;
            }
        };

        $options                                       = getConnectionOptions();
        $options[ConnectionOptions::OPTION_CONNECTION] = 'Close';
        $options[ConnectionOptions::OPTION_TRACE]      = $tracer;

        $connection   = new Connection($options);
        $adminHandler = new AdminHandler($connection);

        $adminHandler->getServerVersion();
        static::assertTrue($done);
    }


    /**
     * Test "connection: close"
     */
    public function testConnectionKeepAlive()
    {
        $done   = false;
        $tracer = function ($type, $data) use (&$done) {
            if ($type === 'send') {
                static::assertNotFalse(stripos($data, 'Connection: Keep-Alive'));
                $done = true;
            }
        };

        $options                                       = getConnectionOptions();
        $options[ConnectionOptions::OPTION_CONNECTION] = 'Keep-Alive';
        $options[ConnectionOptions::OPTION_TRACE]      = $tracer;

        $connection   = new Connection($options);
        $adminHandler = new AdminHandler($connection);

        $adminHandler->getServerVersion();
        static::assertTrue($done);
    }
    
    
    /**
     * Test the authentication
     */
    public function testAuthentication()
    {
        if (!useAuthentication()) {
            $this->markTestSkipped("test is only meaningful with authentication enabled");
        }

        $done   = false;
        $tracer = function ($type, $data) use (&$done) {
            if ($type === 'send') {
                static::assertNotFalse(strpos($data, 'Authorization: Basic ' . base64_encode('theQuickBrownFox:jumped-over-it')));
                $done = true;
            }
        };

        $options                                        = getConnectionOptions();
        $options[ConnectionOptions::OPTION_AUTH_USER]   = 'theQuickBrownFox';
        $options[ConnectionOptions::OPTION_AUTH_PASSWD] = 'jumped-over-it';
        $options[ConnectionOptions::OPTION_TRACE]       = $tracer;

        $connection   = new Connection($options);
        $adminHandler = new AdminHandler($connection);

        $excepted = false;
        try {
            $adminHandler->getServerVersion();
        } catch (ServerException $exception) {
            $excepted = true;
            static::assertEquals(401, $exception->getCode());
        }

        static::assertTrue($excepted);
    }

    /**
     * Test the basic tracer
     */
    public function testBasicTracer()
    {
        //Setup
        $basicTracer = function ($type, $data) {
            static::assertContains(
                $type,
                ['send', 'receive'],
                'Basic tracer\'s type should only be \'send\' or \'receive\''
            );
            static::assertEquals('string', gettype($data), 'Basic tracer data is not a string!.');
        };

        $options                                  = getConnectionOptions();
        $options[ConnectionOptions::OPTION_TRACE] = $basicTracer;

        $connection        = new Connection($options);
        $collectionHandler = new CollectionHandler($connection);

        //Try creating a collection
        $collectionHandler->create('ArangoDB_PHP_TestSuite_TestTracer' . '_' . static::$testsTimestamp);

        //Delete the collection
        try {
            $collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
    }

    /**
     * Test the enhanced tracer
     */
    public function testEnhancedTracer()
    {
        //Setup
        $enhancedTracer = function ($data) {
            static::assertTrue(
                $data instanceof TraceRequest || $data instanceof TraceResponse,
                '$data must be instance of TraceRequest or TraceResponse.'
            );

            static::assertEquals('array', gettype($data->getHeaders()), 'Headers should be an array!');
            static::assertNotEmpty($data->getHeaders(), 'Headers should not be an empty array!');
            static::assertEquals('string', gettype($data->getBody()), 'Body must be a string!');

            if ($data instanceof TraceRequest) {
                static::assertContains(
                    $data->getMethod(),
                    [
                        HttpHelper::METHOD_DELETE,
                        HttpHelper::METHOD_GET,
                        HttpHelper::METHOD_HEAD,
                        HttpHelper::METHOD_PATCH,
                        HttpHelper::METHOD_POST,
                        HttpHelper::METHOD_PUT
                    ],
                    'Invalid http method!'
                );

                static::assertEquals('string', gettype($data->getRequestUrl()), 'Request url must be a string!');
                static::assertEquals('request', $data->getType());

                foreach ($data->getHeaders() as $header => $value) {
                    static::assertEquals('string', gettype($value), 'The header value should be a string');
                    static::assertEquals('string', gettype($header), 'The header should be a string');
                }
            } else {
                static::assertEquals('integer', gettype($data->getHttpCode()), 'Http code must be an integer!');
                static::assertEquals(
                    'string',
                    gettype($data->getHttpCodeDefinition()),
                    'Http code definition must be a string!'
                );
                static::assertEquals('response', $data->getType());
                static::assertIsFloat($data->getTimeTaken());
            }
        };

        $options                                           = getConnectionOptions();
        $options[ConnectionOptions::OPTION_TRACE]          = $enhancedTracer;
        $options[ConnectionOptions::OPTION_ENHANCED_TRACE] = true;

        $connection        = new Connection($options);
        $collectionHandler = new CollectionHandler($connection);

        //Try creating a collection
        $collectionHandler->create('ArangoDB_PHP_TestSuite_TestTracer' . '_' . static::$testsTimestamp);

        //Delete the collection
        try {
            $collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer' . '_' . static::$testsTimestamp);
        } catch (Exception $e) {
        }
    }

    public function tearDown(): void
    {
        unset($this->connection);

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestTracer' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            //Silence the exception
        }

        unset($this->collectionHandler);
    }
}
