<?php
/**
 * ArangoDB PHP client testsuite
 * File: DocumentBasicTest.php
 *
 * @package ArangoDBClient
 * @author  Frank Mayer
 */

namespace ArangoDBClient;

/**
 * Class DocumentBasicTest
 *
 * @property Connection        $connection
 * @property Collection        $collection
 * @property Collection        $edgeCollection
 * @property CollectionHandler $collectionHandler
 * @property DocumentHandler   $documentHandler
 *
 * @package ArangoDBClient
 */
class DocumentBasicTest extends
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
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01');
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        $this->collection = new Collection();
        $this->collection->setName('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        $this->collectionHandler->create($this->collection);
    }


    /**
     * Test if Document and DocumentHandler instances can be initialized
     */
    public function testInitializeDocument()
    {
        $this->collection        = new Collection();
        $this->collectionHandler = new CollectionHandler($this->connection);
        $document                = new Document();
        static::assertInstanceOf(Document::class, $document);
        static::assertInstanceOf(Document::class, $document);
        unset ($document);
    }
    
    
    /**
     * Try to create a document silently 
     */
    public function testInsertSilent()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 1]);
        $documentHandler = new DocumentHandler($connection);

        $document = $documentHandler->insert($collection->getName(), $document, ['silent' => true]);
        static::assertNull($document);
    }
    
    
    /**
     * Try to create a document silently - with an error
     */
    public function testInsertSilentWithError()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 1]);
        $documentHandler = new DocumentHandler($connection);

        // insert the document once 
        $result = $documentHandler->insert($collection->getName(), $document, ['silent' => true]);
        static::assertNull($result);

        // and try to insert it again
        try {
            $documentHandler->insert($collection->getName(), $document, ['silent' => true ]);
        } catch (\Exception $exception409) {
        }
        static::assertEquals(409, $exception409->getCode());
    }
    
    
    /**
     * Try to create a document and return it
     */
    public function testInsertReturnNew()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 1]);
        $documentHandler = new DocumentHandler($connection);

        $document = $documentHandler->insert($collection->getName(), $document, ['returnNew' => true]);

        static::assertEquals('me', $document['_key']);
        static::assertEquals('me', $document['new']['_key']);
        static::assertEquals(1, $document['new']['value']);
    }
    
    
    /**
     * Try to insert many documents
     */
    public function testInsertMany()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documents       = [];
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 1]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 2]);
        $documents[]     = Document::createFromArray(['_key' => 'test3', 'value' => 3]);
        $documents[]     = Document::createFromArray(['_key' => 'test4', 'value' => 4]);

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), $documents); 
        static::assertTrue(is_array($result));
        static::assertEquals(4, count($result));

        foreach ($result as $i => $doc) {
            static::assertArrayHasKey('_id', $doc);
            static::assertArrayHasKey('_key', $doc);
            static::assertArrayHasKey('_rev', $doc);
            static::assertEquals('test' . ($i + 1) , $doc['_key']);
        }
    }
    
    
    /**
     * Try to insert many documents, return new
     */
    public function testInsertManyReturnNew()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documents       = [];
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 1]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 2]);
        $documents[]     = Document::createFromArray(['_key' => 'test3', 'value' => 3]);
        $documents[]     = Document::createFromArray(['_key' => 'test4', 'value' => 4]);

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), $documents, ['returnNew' => true]);
        static::assertTrue(is_array($result));
        static::assertEquals(4, count($result));

        foreach ($result as $i => $doc) {
            static::assertArrayHasKey('_id', $doc);
            static::assertArrayHasKey('_key', $doc);
            static::assertArrayHasKey('_rev', $doc);
            static::assertEquals('test' . ($i + 1) , $doc['_key']);
            static::assertArrayHasKey('new', $doc);
            static::assertEquals('test' . ($i + 1) , $doc['new']['_key']);
            static::assertEquals($i + 1 , $doc['new']['value']);
        }
    }
    
    
    /**
     * Try to insert many documents, silent
     */
    public function testInsertManySilent()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documents       = [];
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 1]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 2]);
        $documents[]     = Document::createFromArray(['_key' => 'test3', 'value' => 3]);
        $documents[]     = Document::createFromArray(['_key' => 'test4', 'value' => 4]);

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), $documents, ['silent' => true]);
        static::assertTrue(is_array($result));
        
        if (isCluster($this->connection)) {
            static::assertEquals(4, count($result));
        } else {
            static::assertEquals(0, count($result));
        }
    }
    
    
    /**
     * Try to insert many documents, with errors
     */
    public function testInsertManyWithErrors()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documents       = [];
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 1]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 2]);
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 3]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 4]);

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), $documents);
        static::assertTrue(is_array($result));
        static::assertEquals(4, count($result));

        foreach ($result as $i => $doc) {
            if ($i < 2) {
                static::assertArrayHasKey('_id', $doc);
                static::assertArrayHasKey('_key', $doc);
                static::assertArrayHasKey('_rev', $doc);
                static::assertEquals('test' . ($i + 1) , $doc['_key']);
            } else {
                static::assertArrayHasKey('error', $doc);
                static::assertArrayHasKey('errorNum', $doc);
                static::assertArrayHasKey('errorMessage', $doc);
                static::assertTrue($doc['error']);
                static::assertEquals(1210, $doc['errorNum']);
            }
        }
    }
    
    
    /**
     * Try to insert many documents, with errors, silent
     */
    public function testInsertManySilentWithErrors()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documents       = [];
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 1]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 2]);
        $documents[]     = Document::createFromArray(['_key' => 'test1', 'value' => 3]);
        $documents[]     = Document::createFromArray(['_key' => 'test2', 'value' => 4]);

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), $documents, ['silent' => true]);
        static::assertTrue(is_array($result));
        
        if (isCluster($this->connection)) {
            static::assertEquals(4, count($result));

            foreach ($result as $i => $doc) {
              if ($i < 2) {
                  static::assertArrayHasKey('_id', $doc);
                  static::assertArrayHasKey('_key', $doc);
                  static::assertArrayHasKey('_rev', $doc);
                  static::assertEquals('test' . ($i + 1) , $doc['_key']);
              } else {
                  static::assertArrayHasKey('error', $doc);
                  static::assertArrayHasKey('errorNum', $doc);
                  static::assertArrayHasKey('errorMessage', $doc);
                  static::assertTrue($doc['error']);
                  static::assertEquals(1210, $doc['errorNum']);
              }
            }
        } else {
            static::assertEquals(2, count($result));

            foreach ($result as $i => $doc) {
                static::assertArrayHasKey('error', $doc);
                static::assertArrayHasKey('errorNum', $doc);
                static::assertArrayHasKey('errorMessage', $doc);
                static::assertTrue($doc['error']);
                static::assertEquals(1210, $doc['errorNum']);
            }
        }
    }
    
    
    /**
     * Try to insert many documents, large request
     */
    public function testInsertManyLarge()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documents       = [];
        for ($i = 0; $i < 5000; ++$i) {
            $documents[] = ['_key' => 'test' . $i, 'value' => $i];
        }
        $documents[]     = ['_key' => 'test0', 'value' => 2];

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), $documents, ['returnNew' => true]);
        static::assertTrue(is_array($result));
        static::assertEquals(5001, count($result));

        foreach ($result as $i => $doc) {
            if ($i < 5000) {
                static::assertArrayHasKey('_id', $doc);
                static::assertArrayHasKey('_key', $doc);
                static::assertArrayHasKey('_rev', $doc);
                static::assertEquals('test' . $i , $doc['_key']);
                static::assertArrayHasKey('new', $doc);
                static::assertEquals('test' . $i , $doc['new']['_key']);
                static::assertEquals($i , $doc['new']['value']);
            } else {
                static::assertArrayHasKey('error', $doc);
                static::assertArrayHasKey('errorNum', $doc);
                static::assertArrayHasKey('errorMessage', $doc);
                static::assertTrue($doc['error']);
                static::assertEquals(1210, $doc['errorNum']);
            }
        }
    }
    
    /**
     * Try to call insertMany with 0 documents
     */
    public function testInsertManyEmpty()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;

        $documentHandler = new DocumentHandler($connection);

        $result = $documentHandler->insertMany($collection->getName(), []);
        static::assertTrue(is_array($result));
        static::assertEquals(0, count($result));
    }
    
    
    /**
     * Try to create a document and overwrite it, using deprecated overwrite option
     */
    public function testInsertOverwrite()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 1]);
        $documentHandler = new DocumentHandler($connection);

        $document = $documentHandler->insert($collection->getName(), $document, ['returnNew' => true]);

        static::assertEquals('me', $document['_key']);
        static::assertEquals('me', $document['new']['_key']);
        static::assertEquals(1, $document['new']['value']);
        
        try {
            $documentHandler->insert($collection->getName(), $document, ['overwrite' => false]);
        } catch (\Exception $exception409) {
        }
        static::assertEquals(409, $exception409->getCode());
        
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 2]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwrite' => true, 'returnOld' => true, 'returnNew' => true]);
        static::assertEquals('me', $document['_key']);
        static::assertEquals('me', $document['old']['_key']);
        static::assertEquals('me', $document['new']['_key']);
        static::assertEquals(1, $document['old']['value']);
        static::assertEquals(2, $document['new']['value']);

        
        $document        = Document::createFromArray(['_key' => 'other', 'value' => 2]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwrite' => false, 'returnOld' => true, 'returnNew' => true]);

        static::assertEquals('other', $document['_key']);
        static::assertEquals('other', $document['new']['_key']);
        static::assertEquals(2, $document['new']['value']);
        
        $document        = Document::createFromArray(['_key' => 'other', 'value' => 3]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwrite' => true, 'returnOld' => true, 'returnNew' => true]);

        static::assertEquals('other', $document['_key']);
        static::assertEquals('other', $document['old']['_key']);
        static::assertEquals(2, $document['old']['value']);
        static::assertEquals('other', $document['new']['_key']);
        static::assertEquals(3, $document['new']['value']);
        
        $document        = Document::createFromArray(['_key' => 'foo', 'value' => 4]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwrite' => true, 'returnOld' => true, 'returnNew' => true]);

        static::assertEquals('foo', $document['_key']);
        static::assertEquals('foo', $document['new']['_key']);
        static::assertEquals(4, $document['new']['value']);
    }
    
    /**
     * Try to create a document and overwrite it, using overwriteMode option
     */
    public function testInsertOverwriteMode()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 1]);
        $documentHandler = new DocumentHandler($connection);

        $document = $documentHandler->insert($collection->getName(), $document, ['returnNew' => true]);

        static::assertEquals('me', $document['_key']);
        static::assertEquals('me', $document['new']['_key']);
        static::assertEquals(1, $document['new']['value']);

        // conflict mode
        try {
            $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'conflict']);
        } catch (\Exception $exception409) {
        }
        static::assertEquals(409, $exception409->getCode());
        
        $document        = Document::createFromArray(['_key' => 'other-no-conflict', 'value' => 1]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'conflict']);

        static::assertEquals($collection->getName() . '/other-no-conflict', $document);


        // ignore mode        
        $document        = Document::createFromArray(['_key' => 'me', 'value' => 2]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'ignore', 'returnOld' => true, 'returnNew' => true]);

        static::assertEquals('me', $document['_key']);
        static::assertFalse(isset($document['_new']));
        static::assertFalse(isset($document['_old']));
        
        
        $document        = Document::createFromArray(['_key' => 'yet-another', 'value' => 3]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'ignore', 'returnOld' => true, 'returnNew' => true]);

        static::assertEquals('yet-another', $document['_key']);
        static::assertEquals('yet-another', $document['new']['_key']);
        static::assertEquals(3, $document['new']['value']);
        static::assertFalse(isset($document['_old']));
        
        
        $document        = Document::createFromArray(['_key' => 'yet-another', 'value' => 4]);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'ignore']);

        static::assertEquals($collection->getName() . '/yet-another', $document);


        // update mode
        $document        = Document::createFromArray(['_key' => 'me', 'foo' => 'bar']);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'update', 'returnOld' => true, 'returnNew' => true ]);

        static::assertEquals('me', $document['_key']);
        static::assertEquals('me', $document['old']['_key']);
        static::assertEquals(1, $document['old']['value']);
        static::assertEquals('me', $document['new']['_key']);
        static::assertEquals(1, $document['new']['value']);
        static::assertEquals('bar', $document['new']['foo']);


        // replace mode
        $document        = Document::createFromArray(['_key' => 'me', 'qux' => 'qaz']);
        $document = $documentHandler->insert($collection->getName(), $document, ['overwriteMode' => 'replace', 'returnOld' => true, 'returnNew' => true ]);

        static::assertEquals('me', $document['_key']);
        static::assertEquals('me', $document['new']['_key']);
        static::assertEquals(1, $document['old']['value']);
        static::assertEquals('bar', $document['old']['foo']);
        static::assertFalse(isset($document['new']['foo']));
        static::assertFalse(isset($document['new']['value']));
        static::assertEquals('qaz', $document['new']['qux']);
    }


    /**
     * Try to create and delete a document with an existing id
     */
    public function testCreateAndDeleteDocumentWithId()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = Document::createFromArray(['_key' => 'me']);
        $documentHandler = new DocumentHandler($connection);

        $documentId = $documentHandler->save($collection->getName(), $document);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $key = $resultingDocument->getKey();
        static::assertSame('me', $key);

        $id = $resultingDocument->getHandle();
        static::assertSame($collection->getName() . '/' . $key, $id);

        static::assertTrue($documentHandler->remove($document));
    }


    /**
     * Try to create and delete a document
     */
    public function testCreateAndDeleteDocument()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->save($collection->getName(), $document);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);

        static::assertTrue($documentHandler->remove($document));
    }
    
    
    /**
     * Try to create and silently delete a document
     */
    public function testCreateAndDeleteDocumentSilent()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->save($collection->getName(), $document);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);

        static::assertTrue($documentHandler->remove($document, ['silent' => true]));
    }
    
    
    /**
     * Try to create and silently delete a document
     */
    public function testDeleteDocumentSilentWithError()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document = Document::createFromArray(['_key' => 'does-not-exist']);
        try {
            $documentHandler->removeById($collection, $document, ['silent' => true]);
        } catch (\Exception $exception404) {
        }
        static::assertEquals(404, $exception404->getCode());
    }


    /**
     * Try to create and delete a document
     */
    public function testCreateAndDeleteDocumentWithoutCreatedCollection()
    {
        $connection      = $this->connection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->save('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp, $document, ['createCollection' => true]);

        $resultingDocument = $documentHandler->get('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp, $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);

        $documentHandler->remove($document);
    }


    /**
     * Try to create and delete a document with OPTION_CREATE = true
     */
    public function testCreateAndDeleteDocumentWithoutCreatedCollectionAndOptionCreate()
    {
        $connection      = $this->connection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $options = $connection->getOptions();
        $connection->setOption(ConnectionOptions::OPTION_CREATE, true);

        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        $document->someAttribute = 'someValue';

        $documentId = $documentHandler->save('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp, $document);

        $resultingDocument = $documentHandler->get('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp, $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);

        $documentHandler->remove($document);

	$connection->setOption(ConnectionOptions::OPTION_CREATE, $options[ConnectionOptions::OPTION_CREATE]);

    }


    /**
     * Try to create and delete a document using a defined key
     */
    public function testCreateAndDeleteDocumentUsingDefinedKey()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $document        = new Document();
        $documentHandler = new DocumentHandler($connection);

        $document->someAttribute = 'someValue';
        $document->set('_key', 'somevalue01');
        $documentId = $documentHandler->save($collection->getName(), $document);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        $resultingKey       = $resultingDocument->getKey();
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);
        static::assertSame('somevalue01', $resultingKey, 'Resulting Attribute should be "someValue". It\'s :' . $resultingKey);


        $documentHandler->remove($document);
    }

    /**
     * Try to create and delete a document with several keys
     */
    public function testCreateAndDeleteDocumentWithSeveralKeys()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $keys = [
            '_',
            'foo',
            'bar',
            'bar:bar',
            'baz',
            '1',
            '0',
            'a-b-c',
            'a:b',
            'this-is-a-test',
            'FOO',
            'BAR',
            'Bar',
            'bAr',
            '123456',
            '0123456',
            'true',
            'false',
            'a',
            'A',
            'a1',
            'A1',
            '01ab01',
            '01AB01',
            'invalid', # actually valid
            'INVALID', # actually valid
            'inValId', # actually valid
            'abcd-efgh',
            'abcd_efgh',
            'Abcd_Efgh',
            '@',
            '@@',
            'abc@foo.bar',
            '@..abc-@-foo__bar',
            '.foobar',
            '-foobar',
            '_foobar',
            '@foobar',
            '(valid)',
            '%valid',
            '$valid',
            "$\$bill,y'all",
            '\'valid',
            '\'a-key-is-a-key-is-a-key\'',
            'm+ller',
            ';valid',
            ',valid',
            '!valid!',
            ':',
            ':::',
            ':-:-:',
            ';',
            ';;;;;;;;;;',
            '(',
            ')',
            '()xoxo()',
            '%',
            '%-%-%-%',
            ':-)',
            '!',
            '!!!!',
            '\'',
            '\'\'\'\'',
            "this-key's-valid.",
            '=',
            '==================================================',
            '-=-=-=___xoxox-',
            '*',
            '(*)',
            '****',
            '.',
            '...',
            '-',
            '--',
            '_',
            '__'
        ];

        $adminHandler = new AdminHandler($this->connection);
        $version      = preg_replace('/-[a-z0-9]+$/', '', $adminHandler->getServerVersion());

        if (version_compare($version, '2.6.0') >= 0) {
            // 2.6 will also allow the following document keys, while 2.5 will not
            $keys[] = '.';
            $keys[] = ':';
            $keys[] = '@';
            $keys[] = '-.:@';
            $keys[] = 'foo@bar.baz.com';
            $keys[] = ':.foo@bar-bar_bar.baz.com.:';
        }

        foreach ($keys as $key) {
            $document                = new Document();
            $document->someAttribute = 'someValue';
            $document->set('_key', $key);
            $documentId = $documentHandler->save($collection->getName(), $document);

            $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

            $resultingAttribute = $resultingDocument->someAttribute;
            $resultingKey       = $resultingDocument->getKey();
            static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);
            static::assertSame($key, $resultingKey, 'Resulting Attribute should be "someValue". It\'s :' . $resultingKey);

            $documentHandler->remove($document);
        }
    }


    /**
     * Try to create a document with invalid keys
     */
    public function testCreateDocumentWithInvalidKeys()
    {
        $keys = [
            '',
            ' ',
            '  ',
            ' bar',
            'bar ',
            '/',
            '?',
            'abcdef gh',
            'abcxde&',
            'mötörhead',
            'this-key-will-be-too-long-to-be-processed-successfully-would-you-agree-with-me-sure-you-will-because-there-is-a-limit-of-254-characters-per-key-which-this-string-will-not-conform-to-if-you-are-still-reading-this-you-should-probably-do-something-else-right-now-REALLY',
            '#',
            '|',
            'ü',
            '~',
            '<>',
            'µµ',
            'abcd ',
            ' abcd',
            ' abcd ',
            "\\tabcd",
            "\\nabcd",
            "\\rabcd",
            'abcd defg',
            'abcde/bdbg',
            'a/a',
            '/a',
            'adbfbgb/',
            'öööää',
            'müller',
            "\\\"invalid",
            "\\\\invalid",
            "\\\\\\\\invalid",
            '?invalid',
            '#invalid',
            '&invalid',
            '[invalid]'
        ];

        foreach ($keys as $key) {
            $document                = new Document();
            $document->someAttribute = 'someValue';

            $caught = false;
            try {
                $document->set('_key', $key);
            } catch (ClientException $exception) {
                $caught = true;
            }

            static::assertTrue($caught, 'expecting exception to be thrown for key ' . $key);
        }
    }


    /**
     * Try to create and delete a document
     */
    public function testCreateAndDeleteDocumentWithArray()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = ['someAttribute' => 'someValue'];

        $documentId = $documentHandler->save($collection->getName(), $documentArray);

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);

        $documentHandler->removeById($collection->getName(), $documentId);
    }


    /**
     * Try to create, get and delete a document using the revision-
     */
    public function testCreateGetAndDeleteDocumentWithRevision()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = ['someAttribute' => 'someValue'];

        $documentId = $documentHandler->save($collection->getName(), $documentArray);

        $document = $documentHandler->get($collection->getName(), $documentId);

        /**
         * lets get the document in a wrong revision
         */
        try {
            $documentHandler->get(
                $collection->getName(), $documentId, [
                    'ifMatch'  => true,
                    'revision' => 12345
                ]
            );
        } catch (\Exception $exception412) {
        }
        static::assertEquals(412, $exception412->getCode());

        try {
            $documentHandler->get(
                $collection->getName(), $documentId, [
                    'ifMatch'  => false,
                    'revision' => $document->getRevision()
                ]
            );
        } catch (\Exception $exception304) {
        }
        static::assertEquals('Document has not changed.', $exception304->getMessage());

        $resultingDocument = $documentHandler->get($collection->getName(), $documentId);

        $resultingAttribute = $resultingDocument->someAttribute;
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);

        $resultingDocument->set('someAttribute', 'someValue2');
        $resultingDocument->set('someOtherAttribute', 'someOtherValue2');
        $documentHandler->replace($resultingDocument);

        $oldRevision = $documentHandler->get(
            $collection->getName(), $documentId,
            ['revision' => $resultingDocument->getRevision()]
        );
        static::assertEquals($oldRevision->getRevision(), $resultingDocument->getRevision());
        $documentHandler->removeById($collection->getName(), $documentId);
    }

    /**
     * Try to create, head and delete a document
     */
    public function testCreateHeadAndDeleteDocumentWithRevision()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = ['someAttribute' => 'someValue'];

        $documentId = $documentHandler->save($collection->getName(), $documentArray);
        $document   = $documentHandler->get($collection->getName(), $documentId);

        try {
            $documentHandler->getHead($collection->getName(), $documentId, '12345', true);
        } catch (\Exception $e412) {
        }

        static::assertEquals(412, $e412->getCode());

        try {
            $documentHandler->getHead($collection->getName(), 'notExisting');
        } catch (\Exception $e404) {
        }

        static::assertEquals(404, $e404->getCode());


        $result304 = $documentHandler->getHead($collection->getName(), $documentId, $document->getRevision(), false);
        static::assertEquals('"' . $document->getRevision() . '"', $result304['etag']);
        static::assertEquals(0, $result304['content-length']);
        static::assertEquals(304, $result304['httpCode']);

        $result200 = $documentHandler->getHead($collection->getName(), $documentId, $document->getRevision(), true);
        static::assertEquals('"' . $document->getRevision() . '"', $result200['etag']);
        static::assertNotEquals(0, $result200['content-length']);
        static::assertEquals(200, $result200['httpCode']);

        $documentHandler->removeById($collection->getName(), $documentId);
    }


    /**
     * Try to create and delete a document using a defined key
     */
    public function testCreateAndDeleteDocumentUsingDefinedKeyWithArrayAndSaveOnly()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        $documentArray = ['someAttribute' => 'someValue', '_key' => 'somevalue01'];
        $documentId    = $documentHandler->save($collection->getName(), $documentArray);

        $resultingDocument  = $documentHandler->get($collection->getName(), $documentId);
        $resultingAttribute = $resultingDocument->someAttribute;
        $resultingKey       = $resultingDocument->getKey();
        static::assertSame('someValue', $resultingAttribute, 'Resulting Attribute should be "someValue". It\'s :' . $resultingAttribute);
        static::assertSame('somevalue01', $resultingKey, 'Resulting Attribute should be "someValue". It\'s :' . $resultingKey);


        $documentHandler->removeById($collection->getName(), $documentId);
    }

    /**
     * Try to create a document and get valid JSON when cast to string.
     */
    public function testCreateAndVerifyValidJsonIsReturnedWhenCastToString()
    {
        $document = Document::createFromArray(
            ['someAttribute' => 'someValue', 'someOtherAttribute' => 'someOtherValue']
        );

        $stringDocument = (string) $document;

        static::assertSame(
            '{"someAttribute":"someValue","someOtherAttribute":"someOtherValue"}', $stringDocument, 'Resulting Attribute should be {"someAttribute":"someValue","someOtherAttribute":"someOtherValue"}. It\'s :' . $stringDocument
        );

    }


    public function testHasDocumentReturnsFalseIfDocumentDoesNotExist()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);
        static::assertFalse($documentHandler->has($collection->getName(), 'just_a_stupid_document_id_which_does_not_exist'));
    }


    public function testHasDocumentReturnsTrueIfDocumentExists()
    {
        $connection      = $this->connection;
        $collection      = $this->collection;
        $documentHandler = new DocumentHandler($connection);

        // create doc first
        $document                = new Document();
        $document->someAttribute = 'someValue';

        $documentHandler->save($collection->getName(), $document);

        static::assertTrue($this->collectionHandler->has($collection->getName()));
    }


    public function tearDown(): void
    {
        try {
            $this->collectionHandler->drop('ArangoDB_PHP_TestSuite_TestCollection_01' . '_' . static::$testsTimestamp);
        } catch (\Exception $e) {
            // don't bother us, if it's already deleted.
        }

        unset($this->documentHandler, $this->document, $this->collectionHandler, $this->collection, $this->connection);
    }
}
