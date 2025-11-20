<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'API Documentation' }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css" />
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    @php($specData = is_array($specJson) ? $specJson : (json_decode($specJson, true) ?: []))
    <script id="openapi-spec" type="application/json">@json($specData)</script>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script nonce="{{ $cspNonce ?? request()->attributes->get('csp_nonce', '') }}">
        (function () {
            try {
                const raw = document.getElementById('openapi-spec').textContent || '{}';
                const spec = JSON.parse(raw);

                window.ui = SwaggerUIBundle({
                    dom_id: '#swagger-ui',
                    spec: spec,
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    layout: 'BaseLayout',
                    docExpansion: 'none',
                    filter: true,
                });
            } catch (e) {
                console.error('Failed to load OpenAPI spec:', e);
                const el = document.createElement('pre');
                el.textContent = 'Failed to load OpenAPI spec';
                document.body.appendChild(el);
            }
        })();
    </script>
</body>
</html>
