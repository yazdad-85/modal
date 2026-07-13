<?php

namespace Tests\Support;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

abstract class FeatureTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;

    protected $namespace = 'App';

    protected $DBGroup = 'tests';

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = [];
    }

    protected function captureSession(TestResponse $response): TestResponse
    {
        $this->session = $_SESSION;

        return $response;
    }

    protected function postWithCsrf(string $path, array $params = []): TestResponse
    {
        $params[csrf_token()] = csrf_hash();

        return $this->captureSession($this->post($path, $params));
    }

    protected function registerUser(
        string $name,
        string $email,
        string $password = 'password1'
    ): void {
        $userModel = new UserModel();
        $userModel->insert([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
        ]);
    }

    protected function loginAs(string $email, string $password = 'password1'): TestResponse
    {
        return $this->postWithCsrf('login', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    protected function loginAsUser(
        string $name = 'Test User',
        string $email = 'test@example.com',
        string $password = 'password1'
    ): TestResponse {
        $this->registerUser($name, $email, $password);

        return $this->loginAs($email, $password);
    }
}
