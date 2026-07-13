<?php

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\FeatureTestCase;

final class ProjectTest extends FeatureTestCase
{
    public function testCreateProject(): void
    {
        $this->loginAsUser();

        $result = $this->postWithCsrf('projects', [
            'nama_proyek'     => 'Proyek Serer',
            'mode_input'      => 'direct',
            'total_modal'     => 350_000_000,
            'total_hasil_jual'=> 420_000_000,
            'persen_pemodal'  => 60,
            'persen_operator' => 40,
            'nama_operator'   => 'Operator A',
            'investor_nama'   => ['Pemodal A', 'Pemodal B'],
            'investor_modal'  => [150_000_000, 200_000_000],
        ]);

        $result->assertRedirect();
        $this->assertMatchesRegularExpression('#/projects/\d+#', $result->getRedirectUrl());
    }

    public function testCannotAccessOtherUsersProject(): void
    {
        $this->loginAsUser('User A', 'usera@example.com');
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'     => 'Proyek Privat',
            'mode_input'      => 'direct',
            'total_modal'     => 100_000_000,
            'total_hasil_jual'=> 120_000_000,
            'persen_pemodal'  => 60,
            'persen_operator' => 40,
            'nama_operator'   => 'Operator A',
            'investor_nama'   => ['Pemodal A'],
            'investor_modal'  => [100_000_000],
        ]);

        preg_match('#/projects/(\d+)#', $create->getRedirectUrl(), $matches);
        $projectId = (int) $matches[1];

        $this->get('logout');

        $this->loginAsUser('User B', 'userb@example.com');

        $this->expectException(PageNotFoundException::class);

        $this->get('projects/' . $projectId);
    }

    public function testCompleteProject(): void
    {
        $this->loginAsUser();
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'      => 'Proyek Selesai',
            'mode_input'       => 'direct',
            'total_modal'      => 100_000_000,
            'total_hasil_jual' => 120_000_000,
            'persen_pemodal'   => 60,
            'persen_operator'  => 40,
            'nama_operator'    => 'Operator A',
            'investor_nama'    => ['Pemodal A'],
            'investor_modal'   => [100_000_000],
        ]);

        preg_match('#/projects/(\d+)#', $create->getRedirectUrl(), $matches);
        $projectId = (int) $matches[1];

        $result = $this->postWithCsrf('projects/' . $projectId . '/complete', []);
        $result->assertRedirectTo('/dashboard?tab=completed');

        $show = $this->get('projects/' . $projectId);
        $show->assertSee('Selesai');
        $show->assertDontSee('Tandai Selesai');
    }

    public function testCannotEditCompletedProject(): void
    {
        $this->loginAsUser();
        $create = $this->postWithCsrf('projects', [
            'nama_proyek'      => 'Proyek Locked',
            'mode_input'       => 'direct',
            'total_modal'      => 100_000_000,
            'total_hasil_jual' => 120_000_000,
            'persen_pemodal'   => 60,
            'persen_operator'  => 40,
            'nama_operator'    => 'Operator A',
            'investor_nama'    => ['Pemodal A'],
            'investor_modal'   => [100_000_000],
        ]);

        preg_match('#/projects/(\d+)#', $create->getRedirectUrl(), $matches);
        $projectId = (int) $matches[1];

        $this->postWithCsrf('projects/' . $projectId . '/complete', []);

        $edit = $this->get('projects/' . $projectId . '/edit');
        $edit->assertRedirectTo('/projects/' . $projectId);
    }
}
