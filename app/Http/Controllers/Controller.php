<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;

abstract class Controller
{
    protected function apiSuccess(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $meta = []
    ) {
        return ApiResponse::success($data, $message, $status, $meta);
    }

    protected function apiPaginated($paginator, ?string $message = null)
    {
        return ApiResponse::paginated($paginator, $message);
    }

    protected function apiError(string $message, int $status, array $errors = [], array $meta = [])
    {
        return ApiResponse::error($message, $status, $errors, $meta);
    }

    protected function apiNoContent()
    {
        return ApiResponse::noContent();
    }
}
