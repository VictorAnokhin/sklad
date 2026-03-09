<?php namespace App\Exceptions;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
class Handler extends ExceptionHandler {
    protected $dontReport = [];
    protected $dontFlash = ['password', 'pass'];
    public function register() {}
}
