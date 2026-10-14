<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentationTest extends TestCase
{
    public function test_root_redirects_to_docs(): void
    {
        $this->get('/')->assertRedirect('/docs');
    }

    public function test_docs_page_is_accessible(): void
    {
        $this->get('/docs')
            ->assertOk()
            ->assertSee('SwaggerUIBundle');
    }

    public function test_api_docs_alias_is_accessible(): void
    {
        $this->get('/api/docs')
            ->assertOk()
            ->assertSee('SwaggerUIBundle');
    }

    public function test_openapi_json_is_accessible_and_valid(): void
    {
        $response = $this->get('/openapi.json');
        $response->assertOk();
        $response->assertJsonPath('openapi', '3.0.3');
        $response->assertJsonPath('info.title', 'Courier Management API');
    }

    public function test_openapi_yaml_is_accessible(): void
    {
        $this->get('/openapi.yaml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/yaml; charset=UTF-8');
    }

    public function test_postman_collection_is_accessible_and_valid(): void
    {
        $response = $this->get('/courier-management-api.postman_collection.json');
        $response->assertOk();
        $response->assertJsonPath('info.name', 'Courier Management API');
        $response->assertJsonPath('info.schema', 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json');
    }
}
