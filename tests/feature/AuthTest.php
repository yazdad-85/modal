<?php

namespace Tests\Feature;

use Tests\Support\FeatureTestCase;

final class AuthTest extends FeatureTestCase
{
    public function testRegisterAndLogin(): void
    {
        $this->registerUser('Test User', 'test@example.com', 'password1');

        $result = $this->loginAs('test@example.com', 'password1');

        $result->assertRedirectTo('/dashboard');
    }

    public function testLoginWrongPassword(): void
    {
        $this->registerUser('Test User', 'test@example.com', 'password1');

        $result = $this->loginAs('test@example.com', 'wrongpassword');

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    public function testLogout(): void
    {
        $this->loginAsUser();

        $result = $this->get('logout');

        $result->assertRedirectTo('/login');
    }
}
