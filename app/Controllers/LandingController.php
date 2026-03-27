<?php
class LandingController
{
    public function index(): void
    {
        require_once APP_PATH . '/Views/pages/landing.php';
    }
}
