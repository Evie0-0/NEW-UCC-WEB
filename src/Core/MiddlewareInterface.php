<?php
declare(strict_types=1);

namespace Core;

use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface MiddlewareInterface {
    public function handle(Request $request, Closure $next): Response;
}
