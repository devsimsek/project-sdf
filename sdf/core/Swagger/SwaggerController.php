<?php

declare(strict_types=1);

namespace SDF\Swagger;

use SDF\Controller;

/**
 * smskSoft SDF Swagger Controller
 * Copyright devsimsek
 * @package     SDF
 * @subpackage  SDF\Swagger
 * @file        SwaggerController.php
 * @version     v1.0.0
 * @author      devsimsek
 * @copyright   Copyright (c) 2025, smskSoft, devsimsek
 * @license     https://opensource.org/licenses/MIT	MIT License
 * @url         https://github.com/devsimsek/project-sdf/wiki/libraries/swagger
 * @since       Version 2.0
 * @filesource
 */
class SwaggerController extends Controller
{
    /**
     * GET /api/openapi.json
     *
     * Expose the generated OpenAPI 3.x spec as JSON.
     *
     * @return void
     */
    public function spec(): void
    {
        $generator = new SwaggerGenerator(
            title: 'SDF API',
            apiVersion: SDF_VERSION,
            description: 'SDF Framework API documentation - automatically generated from #[OA\...] attributes.',
        );

        $this->response
            ->json(json_decode($generator->generate(), true));
    }

    /**
     * GET /api/docs
     *
     * Render Swagger UI via CDN.
     *
     * @return void
     */
    public function docs(): void
    {
        $specUrl = '/api/openapi.json';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDF API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: '{$specUrl}',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.SwaggerUIStandalonePreset,
            ],
            layout: "BaseLayout",
        });
    </script>
</body>
</html>
HTML;

        echo $html;
    }
}
