<?php

declare(strict_types=1);

namespace BeLocal\Tests\Unit;

use BeLocal\BeLocalEngine;
use BeLocal\BeLocalError;
use BeLocal\Transport;
use BeLocal\TranslateResponse;
use PHPUnit\Framework\TestCase;

class BeLocalEngineTest extends TestCase
{
    /**
     * Test the translate method with a successful response
     */
    public function testTranslateSuccess()
    {
        $transport = $this->createMock(Transport::class);

        $responseData = ['text' => 'Bonjour', 'status' => 'cached'];
        $response = new TranslateResponse($responseData, true, null, 200, null, null);

        $transport->expects($this->once())
            ->method('send')
            ->with(['text' => 'Hello', 'lang' => 'fr'])
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->translate('Hello', 'fr');

        $this->assertTrue($result->isOk());
        $this->assertEquals('Bonjour', $result->getText());
    }

    /**
     * Test the translate method with an error response
     */
    public function testTranslateError()
    {
        $transport = $this->createMock(Transport::class);

        $error = new BeLocalError('TEST_ERROR', 'Test error message');
        $response = new TranslateResponse(null, false, $error, 500, null, null);

        $transport->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->translate('Hello', 'fr');

        $this->assertFalse($result->isOk());
        $this->assertSame($error, $result->getError());
    }

    /**
     * Test the translate method with empty text
     */
    public function testTranslateEmptyText()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->never())
            ->method('send');

        $engine = new BeLocalEngine($transport);

        $result = $engine->translate('', 'fr');

        $this->assertFalse($result->isOk());
        $this->assertEquals('', $result->getText());
    }

    /**
     * Test the translateMany method with a successful response
     */
    public function testTranslateManySuccess()
    {
        $transport = $this->createMock(Transport::class);

        $responseData = [
            'results' => [
                [
                    'requestId' => '123',
                    'data' => ['text' => 'Bonjour']
                ],
                [
                    'requestId' => '456',
                    'data' => ['text' => 'Au revoir']
                ]
            ]
        ];
        $response = new TranslateResponse($responseData, true, null, 200, null, null);

        $transport->expects($this->once())
            ->method('sendBatch')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->translateMany(['Hello', 'Goodbye'], 'fr');

        $this->assertTrue($result->isOk());
        $this->assertCount(2, $result->getTexts());
    }

    /**
     * Test the t method (sugar for translate)
     */
    public function testTMethod()
    {
        $transport = $this->createMock(Transport::class);

        $responseData = ['text' => 'Bonjour', 'status' => 'translated'];
        $response = new TranslateResponse($responseData, true, null, 200, null, null);

        $transport->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->t('Hello', 'fr');

        $this->assertEquals('Bonjour', $result);
    }

    /**
     * Test the tMany method (sugar for translateMany)
     */
    public function testTManyMethod()
    {
        $transport = $this->createMock(Transport::class);

        // Create a mock response that will return translated texts in the same order
        $transport->expects($this->once())
            ->method('sendBatch')
            ->willReturnCallback(fn($data) => new TranslateResponse(
                ['results' => array_map(fn($item) => [
                    'requestId' => $item['requestId'],
                    'data' => [
                        'text' => ($item['payload']['text'] === 'Hello') ? 'Bonjour' : 'Au revoir',
                        'status' => 'translated'
                    ]
                ], $data['batch'])],
                true,
                null,
                200,
                null,
                null
            ));

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello', 'Goodbye'], 'fr');

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
            ->method('sendBatch')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->tMany(['Hello', 'Goodbye'], 'fr');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Hello', $result[0]);
        $this->assertEquals('Goodbye', $result[1]);
    }

    /**
     * Test the translate method with status = error
     */
    public function testTranslateStatusError()
    {
        $transport = $this->createMock(Transport::class);

        $responseData = ['text' => 'Some text', 'status' => 'error'];
        $response = new TranslateResponse($responseData, true, null, 200, null, null);

        $transport->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $result = $engine->translate('Hello', 'fr');

        $this->assertFalse($result->isOk());
        $this->assertNull($result->getText());
    }

    /**
     * Test the translateMany method with status = error
     */
    public function testTranslateManyStatusError()
    {
        $transport = $this->createMock(Transport::class);

        // Create a mock response with status = error for all items
        $transport->expects($this->once())
            ->method('sendBatch')
            ->willReturnCallback(fn($data) => new TranslateResponse(
                ['results' => array_map(fn($item) => [
                    'requestId' => $item['requestId'],
                    'data' => ['text' => 'Some text', 'status' => 'error']
                ], $data['batch'])],
                true,
                null,
                200,
                null,
                null
            ));

        $engine = new BeLocalEngine($transport);

        $result = $engine->translateMany(['Hello', 'Goodbye'], 'fr');

        $this->assertFalse($result->isOk());
        $this->assertCount(2, $result->getTexts());
        $this->assertNull($result->getTexts()[0]);
        $this->assertNull($result->getTexts()[1]);
    }

    /**
     * Test that translateMany throws InvalidArgumentException when array contains non-string elements
     */
    public function testTranslateManyWithNonStringInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->translateMany(['Hello', 123, 'World'], 'fr');
    }

    /**
     * Test that translateMany throws InvalidArgumentException when array contains null
     */
    public function testTranslateManyWithNullInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->translateMany(['Hello', null, 'World'], 'fr');
    }

    /**
     * Test that translateMany throws InvalidArgumentException when array contains array element
     */
    public function testTranslateManyWithArrayInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->translateMany(['Hello', ['nested'], 'World'], 'fr');
    }

    /**
     * Test that translateMany throws InvalidArgumentException when context has non-string keys
     */
    public function testTranslateManyWithNonStringKeyInContext()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context keys and values must be strings');

        $context = [0 => 'value', 'key' => 'value2'];
        $engine->translateMany(['Hello', 'World'], 'fr', '', $context);
    }

    /**
     * Test that translate throws InvalidArgumentException when context has non-string keys
     */
    public function testTranslateWithNonStringKeyInContext()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Context keys and values must be strings');

        $context = [123 => 'value', 'key' => 'value2'];
        $engine->translate('Hello', 'fr', '', $context);
    }

    /**
     * Test that translateMany works correctly with valid context
     */
    public function testTranslateManyWithValidContext()
    {
        $transport = $this->createMock(Transport::class);

        $responseData = [
            'results' => [
                [
                    'requestId' => '123',
                    'data' => ['text' => 'Bonjour', 'status' => 'translated']
                ]
            ]
        ];
        $response = new TranslateResponse($responseData, true, null, 200, null, null);

        $transport->expects($this->once())
            ->method('sendBatch')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $context = ['user_ctx' => 'test', 'cache_type' => 'editable'];
        $result = $engine->translateMany(['Hello'], 'fr', '', $context);

        $this->assertTrue($result->isOk());
    }

    /**
     * Test that translate works correctly with valid context
     */
    public function testTranslateWithValidContext()
    {
        $transport = $this->createMock(Transport::class);

        $responseData = ['text' => 'Bonjour', 'status' => 'translated'];
        $response = new TranslateResponse($responseData, true, null, 200, null, null);

        $transport->expects($this->once())
            ->method('send')
            ->willReturn($response);

        $engine = new BeLocalEngine($transport);

        $context = ['user_ctx' => 'test', 'cache_type' => 'editable'];
        $result = $engine->translate('Hello', 'fr', '', $context);

        $this->assertTrue($result->isOk());
        $this->assertEquals('Bonjour', $result->getText());
    }

    /**
     * Test that translateMany works correctly with empty array (validation passes, early return)
     */
    public function testTranslateManyWithEmptyArray()
    {
        $transport = $this->createMock(Transport::class);

        $transport->expects($this->never())
            ->method('sendBatch');

        $engine = new BeLocalEngine($transport);

        $result = $engine->translateMany([], 'fr');

        $this->assertFalse($result->isOk());
        $this->assertEquals([], $result->getTexts());
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

        $engine->tMany(['Hello', 456, 'World'], 'fr');
    }

    /**
     * Test that tManyEditable throws InvalidArgumentException when array contains non-string elements
     */
    public function testTManyEditableWithNonStringInArray()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected array<string>, but element at index');

        $engine->tManyEditable(['Hello', true, 'World'], 'fr');
    }

    /**
     * Test that buildRequestId returns the same result for identical inputs
     * This is critical for request deduplication
     */
    public function testBuildRequestIdDeterministic()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('buildRequestId');
        $method->setAccessible(true);

        $text = 'Hello World';
        $lang = 'fr';
        $context1 = ['user_ctx' => 'test', 'cache_type' => 'editable'];
        $context2 = ['cache_type' => 'editable', 'user_ctx' => 'test']; // Same data, different order

        $requestId1 = $method->invoke($engine, $text, $lang, $context1);
        $requestId2 = $method->invoke($engine, $text, $lang, $context2);

        $this->assertEquals($requestId1, $requestId2, 'buildRequestId should return the same result for identical inputs regardless of key order');
    }


    /**
     * Test that buildRequestId returns different results for different inputs
     */
    public function testBuildRequestIdDifferentForDifferentInputs()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('buildRequestId');
        $method->setAccessible(true);

        $text1 = 'Hello';
        $text2 = 'World';
        $lang = 'fr';
        $context = ['user_ctx' => 'test'];

        $requestId1 = $method->invoke($engine, $text1, $lang, $context);
        $requestId2 = $method->invoke($engine, $text2, $lang, $context);

        $this->assertNotEquals($requestId1, $requestId2, 'buildRequestId should return different results for different texts');
    }

    public function testBuildRequestIdDoesNotModifyContext()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $reflection = new \ReflectionClass($engine);
        $method = $reflection->getMethod('buildRequestId');
        $method->setAccessible(true);

        $texts = ['Hello', 'World', 'Test'];
        $lang = 'fr';
        $context = ['cache_type' => 'editable', 'user_ctx' => 'test']; // Unsorted order

        // Get original keys order
        $originalKeys = array_keys($context);
        $requestIds = [];

        // Simulate calling buildRequestId multiple times in a loop (like in translateMany)
        foreach ($texts as $text) {
            $requestId = $method->invoke($engine, $text, $lang, $context);
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

    public function testTManyEditableSameRequestIdForSameParameters()
    {
        $transport = $this->createMock(Transport::class);
        $engine = new BeLocalEngine($transport);

        $capturedRequests = [];

        $transport->expects($this->exactly(2))
            ->method('sendBatch')
            ->willReturnCallback(function($data) use (&$capturedRequests) {
                $capturedRequests[] = $data['batch'];
                return new TranslateResponse(
                    ['results' => array_map(fn($item) => [
                        'requestId' => $item['requestId'],
                        'data' => ['text' => 'Translated', 'status' => 'translated']
                    ], $data['batch'])],
                    true,
                    null,
                    200,
                    null,
                    null
                );
            });

        // First call
        $engine->tManyEditable(['Karcher SC 3 EasyFix STEAM CLEANER'], 'ru', '', 'product');
        
        // Second call with identical parameters
        $engine->tManyEditable(['Karcher SC 3 EasyFix STEAM CLEANER'], 'ru', '', 'product');

        // Verify that both calls generated the same requestId
        $this->assertCount(2, $capturedRequests);
        $this->assertCount(1, $capturedRequests[0]);
        $this->assertCount(1, $capturedRequests[1]);
        
        $requestId1 = $capturedRequests[0][0]['requestId'];
        $requestId2 = $capturedRequests[1][0]['requestId'];
        
        $this->assertEquals($requestId1, $requestId2, 'tManyEditable should generate the same requestId for identical parameters');
    }
}
