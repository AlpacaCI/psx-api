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

use PSX\Api\Parser\Raml;
use PSX\Schema\PropertyInterface;

/**
 * RamlTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class RamlTest extends ParserTestCase
{
    protected function getResource()
    {
        return $this->apiManager->getApi(__DIR__ . '/raml/simple.raml', '/foo');
    }

    public function testParseComplex()
    {
        $resource = Raml::fromFile(__DIR__ . '/raml/test.raml', '/foo');

        $this->assertInstanceOf('PSX\Api\Resource', $resource);
        $this->assertEquals(array('GET', 'POST'), $resource->getAllowedMethods());
        $this->assertEquals('Bar', $resource->getTitle());
        $this->assertEquals('Some description', $resource->getDescription());

        // check GET
        $this->assertEquals('Informations about the method', $resource->getMethod('GET')->getDescription());

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters()->getProperty('pages'));
        $this->assertEquals('The number of pages to return', $resource->getMethod('GET')->getQueryParameters()->getProperty('pages')->getDescription());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters()->getProperty('param_integer'));
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters()->getProperty('param_number'));
        $this->assertEquals('The number', $resource->getMethod('GET')->getQueryParameters()->getProperty('param_number')->getDescription());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters()->getProperty('param_date'));
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters()->getProperty('param_boolean'));
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('GET')->getQueryParameters()->getProperty('param_string'));
        $this->assertParameters($resource->getMethod('GET')->getQueryParameters());

        // check POST
        $this->assertInstanceOf('PSX\Api\Resource\Post', $resource->getMethod('POST'));

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getMethod('POST')->getQueryParameters());
        $this->assertInstanceOf('PSX\Schema\SchemaInterface', $resource->getMethod('POST')->getResponse(200));

        $property = $resource->getMethod('POST')->getRequest();

        $this->assertInstanceOf('PSX\Schema\SchemaInterface', $property);
        $this->assertEquals('A canonical song', $property->getDefinition()->getDescription());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property->getDefinition()->getProperty('title'));
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property->getDefinition()->getProperty('artist'));

        $property = $resource->getMethod('POST')->getResponse(200);

        $this->assertInstanceOf('PSX\Schema\SchemaInterface', $property);
        $this->assertEquals('A canonical song', $property->getDefinition()->getDescription());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property->getDefinition()->getProperty('title'));
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property->getDefinition()->getProperty('artist'));
    }

    public function testParsePath()
    {
        $resource = Raml::fromFile(__DIR__ . '/raml/test.raml', '/bar/:bar_id');

        $this->assertInstanceOf('PSX\Api\Resource', $resource);
        $this->assertEquals(array('GET'), $resource->getAllowedMethods());
        $this->assertEquals('Returns details about bar', $resource->getDescription());

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $resource->getPathParameters());
        $this->assertParameters($resource->getPathParameters());

        $this->assertInstanceOf('PSX\Schema\SchemaInterface', $resource->getMethod('GET')->getResponse(200));

        $property = $resource->getMethod('GET')->getResponse(200)->getDefinition();

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property);
        $this->assertEquals('A canonical song', $property->getDescription());
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property->getProperty('title'));
        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $property->getProperty('artist'));
    }

    public function testParseNested()
    {
        $resource = Raml::fromFile(__DIR__ . '/raml/test.raml', '/foo/bar');

        $this->assertInstanceOf('PSX\Api\Resource', $resource);
        $this->assertEquals(array('GET'), $resource->getAllowedMethods());
        $this->assertEquals('Some description', $resource->getDescription());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testParseResponseWithoutSchema()
    {
        $resource = Raml::fromFile(__DIR__ . '/raml/test.raml', '/foo');

        $resource->getMethod('POST')->getResponse(500);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testParseInvalidPath()
    {
        Raml::fromFile(__DIR__ . '/raml/test.raml', '/test');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testParseInvalidSchema()
    {
        Raml::fromFile(__DIR__ . '/raml/test.raml', '/invalid_schema');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testParseInvalidSchemaReference()
    {
        Raml::fromFile(__DIR__ . '/raml/test.raml', '/invalid_reference');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testFromFileNotExistingFile()
    {
        Raml::fromFile(__DIR__ . '/raml/foo.raml', '/bar/:bar_id');
    }

    protected function assertParameters(PropertyInterface $parameters)
    {
        $this->assertEquals(8, $parameters->getProperty('param_integer')->getMinimum());
        $this->assertEquals(16, $parameters->getProperty('param_integer')->getMaximum());

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $parameters->getProperty('param_number'));
        $this->assertEquals('The number', $parameters->getProperty('param_number')->getDescription());

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $parameters->getProperty('param_date'));

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $parameters->getProperty('param_boolean'));

        $this->assertInstanceOf('PSX\Schema\PropertyInterface', $parameters->getProperty('param_string'));
        $this->assertEquals(8, $parameters->getProperty('param_string')->getMinLength());
        $this->assertEquals(16, $parameters->getProperty('param_string')->getMaxLength());
        $this->assertEquals('[A-z]+', $parameters->getProperty('param_string')->getPattern());
        $this->assertEquals(['foo', 'bar'], $parameters->getProperty('param_string')->getEnum());
    }
}
