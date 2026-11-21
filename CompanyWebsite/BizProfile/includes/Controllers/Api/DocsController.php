<?php

namespace BizProfile\Controllers\Api;

use BizProfile\Core\Controller;
use BizProfile\Core\ApiDocumentation;

class DocsController extends Controller
{
    public function index(): void
    {
        header('Content-Type: application/json');
        echo json_encode(ApiDocumentation::generate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
