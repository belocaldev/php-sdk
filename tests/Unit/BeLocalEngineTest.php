<?php

declare(strict_types=1);

namespace BeLocal\Tests\Unit;

use BeLocal\BeLocalEngine;
use BeLocal\BeLocalError;
use BeLocal\Transport;
use BeLocal\TranslateResponse;
use BeLocal\TranslateRequest;
use BeLocal\TranslateManyResult;
use PHPUnit\Framework\TestCase;

class BeLocalEngineTest extends TestCase
{
    /**
     * Test the t method with a successful response
     */
    public function testTSuccess()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Bonjour'], 'status' => 'cached']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('Hello', 'fr', null, 'test context');

        $this->assertEquals('Bonjour', $result);
    }

    /**
     * Test the t method with an error response (should return original text)
     */
    public function testTError()
    {
        $transport = $this->createMock(Transport::class);

        $error = new BeLocalError('TEST_ERROR', 'Test error message');
        $response = new TranslateResponse(null, false, $error, 500, null, null);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('Hello', 'fr', null, 'test context');

        // On error, t() returns original text
        $this->assertEquals('Hello', $result);
    }

    /**
     * Test the t method with empty text (should return empty string)
     */
    public function testTEmptyText()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => [''], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('', 'fr', null, 'test context');

        $this->assertEquals('', $result);
    }

    /**
     * Test the tMany method with a successful response
     */
    public function testTManySuccess()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Bonjour', 'Au revoir'], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello', 'Goodbye'], 'fr', null, 'test context');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Bonjour', $result[0]);
        $this->assertEquals('Au revoir', $result[1]);
    }

    /**
     * Test the t method (sugar for translate)
     */
    public function testTMethod()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Bonjour'], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('Hello', 'fr', null, 'test context');

        $this->assertEquals('Bonjour', $result);
    }

    /**
     * Test the tMany method (sugar for translateMany)
     */
    public function testTManyMethod()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Bonjour', 'Au revoir'], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello', 'Goodbye'], 'fr', null, 'test context');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Bonjour', $result[0]);
        $this->assertEquals('Au revoir', $result[1]);
    }

    /**
     * Test the tMany method with fallback
     */
    public function testTManyMethodWithFallback()
    {
        $transport = $this->createMock(Transport::class);

        $error = new BeLocalError('TEST_ERROR', 'Test error message');
        $response = new TranslateResponse(null, false, $error, 500, null, null);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello', 'Goodbye'], 'fr', null, 'test context');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Hello', $result[0]);
        $this->assertEquals('Goodbye', $result[1]);
    }

    /**
     * Test the t method with status = error (should return original text)
     */
    public function testTStatusError()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Some text'], 'status' => 'error']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('Hello', 'fr', null, 'test context');

        // On error status, t() returns original text
        $this->assertEquals('Hello', $result);
    }

    /**
     * Test the tMany method with status = error (should return original texts)
     */
    public function testTManyStatusError()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Some text', 'Some text'], 'status' => 'error']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello', 'Goodbye'], 'fr', null, 'test context');

        // On error status, tMany() returns original texts
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Hello', $result[0]);
        $this->assertEquals('Goodbye', $result[1]);
    }

    /**
     * Test that tMany throws InvalidArgumentException when array contains non-string elements
     */
    public function testTManyWithNonStringInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->tMany(['Hello', 123, 'World'], 'fr', null, 'test context');
    }

    /**
     * Test that tMany throws InvalidArgumentException when array contains null
     */
    public function testTManyWithNullInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->tMany(['Hello', null, 'World'], 'fr', null, 'test context');
    }

    /**
     * Test that tMany throws InvalidArgumentException when array contains array element
     */
    public function testTManyWithArrayInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->tMany(['Hello', ['nested'], 'World'], 'fr', null, 'test context');
    }

    /**
     * Test that translateRequest throws InvalidArgumentException when context has non-string keys
     */
    public function testTranslateRequestWithNonStringKeyInContext()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context keys and values must be strings');

        $context = [0 => 'value', 'key' => 'value2'];
        $request = new TranslateRequest(['Hello', 'World'], 'fr', null, $context);
        $engine->translateRequest($request);
    }

    /**
     * Test that translateRequest throws InvalidArgumentException when context has non-string keys
     */
    public function testTranslateRequestWithNonStringKeyInContextSingle()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context keys and values must be strings');

        $context = [123 => 'value', 'key' => 'value2'];
        $request = new TranslateRequest(['Hello'], 'fr', null, $context);
        $engine->translateRequest($request);
    }

    /**
     * Test that tMany works correctly with valid context
     */
    public function testTManyWithValidContext()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Bonjour'], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello'], 'fr', null, 'test context');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Bonjour', $result[0]);
    }

    /**
     * Test that t works correctly with valid context
     */
    public function testTWithValidContext()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => ['Bonjour'], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('Hello', 'fr', null, 'test context');

        $this->assertEquals('Bonjour', $result);
    }

    /**
     * Test that tMany works correctly with empty array (should return empty array)
     */
    public function testTManyWithEmptyArray()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->once())
            ->method('sendMulti')
            ->willReturnCallback(function($data) {
                $request_id = $data['requests'][0]['request_id'];
                return new TranslateResponse(
                    [
                        'results' => [
                            [
                                'request_id' => $request_id,
                                'data' => ['texts' => [], 'status' => 'translated']
                            ]
                        ]
                    ],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany([], 'fr', null, 'test context');

        $this->assertIsArray($result);
        $this->assertEquals([], $result);
    }

    /**
     * Test that tManyManaged throws InvalidArgumentException when array contains non-string elements
     */
    public function testTManyManagedWithNonStringInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->tManyManaged(['Hello', true, 'World'], 'fr', null, 'test context');
    }

    /**
     * Test that buildRequestId returns the same result for identical inputs
     * This is critical for request deduplication
     */
    public function testBuildRequestIdDeterministic()
    {
        $text = 'Hello World';
        $lang = 'fr';
        $context1 = ['user_ctx' => 'test', 'cache_type' => 'managed'];
        $context2 = ['cache_type' => 'managed', 'user_ctx' => 'test']; // Same data, different order

        $request1 = new TranslateRequest([$text], $lang, null, $context1);
        $request2 = new TranslateRequest([$text], $lang, null, $context2);

        $requestId1 = $request1->getRequestId();
        $requestId2 = $request2->getRequestId();

        $this->assertEquals($requestId1, $requestId2, 'buildRequestId should return the same result for identical inputs regardless of key order');
    }


    /**
     * Test that buildRequestId returns different results for different inputs
     */
    public function testBuildRequestIdDifferentForDifferentInputs()
    {
        $text1 = 'Hello';
        $text2 = 'World';
        $lang = 'fr';
        $context = ['user_ctx' => 'test'];

        $request1 = new TranslateRequest([$text1], $lang, null, $context);
        $request2 = new TranslateRequest([$text2], $lang, null, $context);

        $requestId1 = $request1->getRequestId();
        $requestId2 = $request2->getRequestId();

        $this->assertNotEquals($requestId1, $requestId2, 'buildRequestId should return different results for different texts');
    }

    public function testBuildRequestIdDoesNotModifyContext()
    {
        $texts = ['Hello', 'World', 'Test'];
        $lang = 'fr';
        $context = ['cache_type' => 'managed', 'user_ctx' => 'test']; // Unsorted order

        // Get original keys order
        $originalKeys = array_keys($context);
        $requestIds = [];

        // Simulate calling buildRequestId multiple times in a loop (like in translateMany)
        foreach ($texts as $text) {
            $request = new TranslateRequest([$text], $lang, null, $context);
            $requestId = $request->getRequestId();
            $requestIds[] = $requestId;
            
            // Verify context array keys order is unchanged after each call
            $keysAfterCall = array_keys($context);
            $this->assertEquals($originalKeys, $keysAfterCall, 'buildRequestId should not modify the original context array even after multiple calls');
        }

        // Verify all requestIds for the same text are the same
        $firstRequestId = $requestIds[0];
        foreach ($requestIds as $index => $requestId) {
            if ($index > 0 && $texts[$index] === $texts[0]) {
                $this->assertEquals($firstRequestId, $requestId, 'buildRequestId should return the same result for identical inputs');
            }
        }
    }

    public function testTManyManagedSameRequestIdForSameParameters()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $capturedRequests = [];

        $transport->expects($this->exactly(2))
            ->method('sendMulti')
            ->willReturnCallback(function($data) use (&$capturedRequests) {
                $capturedRequests[] = $data['requests'];
                return new TranslateResponse(
                    ['results' => array_map(fn($item) => [
                        'request_id' => $item['request_id'],
                        'data' => ['texts' => ['Translated'], 'status' => 'translated']
                    ], $data['requests'])],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        // First call
        $engine->tManyManaged(['Karcher SC 3 EasyFix STEAM CLEANER'], 'ru', null, 'product');
        
        // Second call with identical parameters
        $engine->tManyManaged(['Karcher SC 3 EasyFix STEAM CLEANER'], 'ru', null, 'product');

        // Verify that both calls generated the same request_id
        $this->assertCount(2, $capturedRequests);
        $this->assertCount(1, $capturedRequests[0]);
        $this->assertCount(1, $capturedRequests[1]);
        
        $request_id1 = $capturedRequests[0][0]['request_id'];
        $request_id2 = $capturedRequests[1][0]['request_id'];
        
        $this->assertEquals($request_id1, $request_id2, 'tManyManaged should generate the same request_id for identical parameters');
    }
}
