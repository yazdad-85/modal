<?php

namespace Tests\Feature;

use CodeIgniter\Security\Exceptions\SecurityException;
use Tests\Support\FeatureTestCase;

final class ExportTest extends FeatureTestCase
{
    public function testPdfExportRequiresAuth(): void
    {
        $result = $this->get('projects/1/export/pdf');

        $result->assertRedirectTo('/login');
    }

    public function testCsrfRejection(): void
    {
        $this->expectException(SecurityException::class);
        $this->expectExceptionCode(403);

        $this->withHeaders(['X-CSRF-TOKEN' => 'invalid-token']);

        $this->post('login', [
            'email'    => 'test@example.com',
            'password' => 'password1',
        ]);
    }
}
