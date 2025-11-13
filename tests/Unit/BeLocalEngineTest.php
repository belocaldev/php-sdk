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
            ->willReturnCallback(function($data) {
                $results = [];
                foreach ($data['batch'] as $item) {
                    $requestId = $item['requestId'];
                    $text = $item['payload']['text'];
                    $translatedText = ($text === 'Hello') ? 'Bonjour' : 'Au revoir';
                    $results[] = [
                        'requestId' => $requestId,
                        'data' => ['text' => $translatedText, 'status' => 'translated']
                    ];
                }
                $responseData = ['results' => $results];
                return new TranslateResponse($responseData, true, null, 200, null, null);
            });

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
            ->willReturnCallback(function($data) {
                $results = [];
                foreach ($data['batch'] as $item) {
                    $requestId = $item['requestId'];
                    $results[] = [
                        'requestId' => $requestId,
                        'data' => ['text' => 'Some text', 'status' => 'error']
                    ];
                }
                $responseData = ['results' => $results];
                return new TranslateResponse($responseData, true, null, 200, null, null);
            });

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
        $this->expectExceptionMessage('Context keys must be strings');

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
        $this->expectExceptionMessage('Context keys must be strings');

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
}
