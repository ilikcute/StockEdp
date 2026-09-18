<?php

/**
 * IDE Helper for dynamic macros registered in Laravel.
 * This file is purely for static analysis / IDE autocompletion and is not executed.
 */

namespace Illuminate\Contracts\Routing {
    /**
     * @method \Illuminate\Http\JsonResponse api(mixed $data = null, string $message = 'Success', int $status = 200)
     */
    interface ResponseFactory {}
}

namespace Illuminate\Routing {
    /**
     * @method \Illuminate\Http\JsonResponse api(mixed $data = null, string $message = 'Success', int $status = 200)
     */
    class ResponseFactory {}
}
