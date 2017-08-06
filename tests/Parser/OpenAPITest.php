<?php
/*
 * PSX is a open source PHP framework to develop RESTful APIs.
 * For the current version and informations visit <http://phpsx.org>
 *
 * Copyright 2010-2017 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PSX\Api\Tests\Parser;

use PSX\Api\ApiManager;
use PSX\Api\Parser\OpenAPI;
use PSX\Api\Resource;
use PSX\Schema\PropertyInterface;
use PSX\Schema\SchemaInterface;

/**
 * OpenAPITest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class OpenAPITest extends ParserTestCase
{
    protected function getResource()
    {
        return $this->apiManager->getApi(__DIR__ . '/openapi/simple.json', '/foo', ApiManager::TYPE_OPENAPI);
    }

    public function testParseComplex()
    {
        $resource = OpenAPI::fromFile(__DIR__ . '/openapi/complex.json', '/foo');

        $this->assertEquals('/foo', $resource->getPath());
        $this->assertEquals('Test', $resource->getTitle());
        $this->assertEquals('Test description', $resource->getDescription());

        $path = $resource->getPathParameters();

        $this->assertInstanceOf(PropertyInterface::class, $path);
        $this->assertInstanceOf(PropertyInterface::class, $path->getProperty('fooId'));

        $methods = $resource->getMethods();

        $this->assertEquals(['GET'], array_keys($methods));

        $this->assertEquals('A long **Test** description', $methods['GET']->getDescription());

        $query = $methods['GET']->getQueryParameters();

        $this->assertInstanceOf(PropertyInterface::class, $query->getProperty('bar'));

        $request = $methods['GET']->getRequest();

        $this->assertInstanceOf(SchemaInterface::class, $request);
        $this->assertInstanceOf(PropertyInterface::class, $request->getDefinition()->getProperty('artist'));

        $response = $methods['GET']->getResponse(200);

        $this->assertInstanceOf(SchemaInterface::class, $response);
        $this->assertInstanceOf(PropertyInterface::class, $response->getDefinition()->getProperty('artist'));
    }

    public function testParsePath()
    {
        $resource = OpenAPI::fromFile(__DIR__ . '/openapi/test.json', '/foo/:fooId');

        $this->assertInstanceOf(Resource::class, $resource);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testParseInvalidPath()
    {
        OpenAPI::fromFile(__DIR__ . '/openapi/test.json', '/test');
    }

    protected function assertParameters(PropertyInterface $parameters)
    {
        $this->assertEquals(8, $parameters->getProperty('param_integer')->getMinimum());
        $this->assertEquals(16, $parameters->getProperty('param_integer')->getMaximum());

        $this->assertInstanceOf(PropertyInterface::class, $parameters->getProperty('param_number'));
        $this->assertEquals('The number', $parameters->getProperty('param_number')->getDescription());

        $this->assertInstanceOf(PropertyInterface::class, $parameters->getProperty('param_date'));

        $this->assertInstanceOf(PropertyInterface::class, $parameters->getProperty('param_boolean'));

        $this->assertInstanceOf(PropertyInterface::class, $parameters->getProperty('param_string'));
        $this->assertEquals(8, $parameters->getProperty('param_string')->getMinLength());
        $this->assertEquals(16, $parameters->getProperty('param_string')->getMaxLength());
        $this->assertEquals('[A-z]+', $parameters->getProperty('param_string')->getPattern());
        $this->assertEquals(['foo', 'bar'], $parameters->getProperty('param_string')->getEnum());
    }
}
