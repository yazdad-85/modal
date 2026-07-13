<?php

namespace App\Controllers;

use App\Models\ProjectModel;

class DashboardController extends BaseController
{
    public function index()
    {
        helper(['rupiah', 'url']);

        $userId = (int) session('user_id');
        $model  = new ProjectModel();
        $tab    = $this->request->getGet('tab') === 'completed' ? 'completed' : 'active';

        return view('dashboard/index', [
            'tab'              => $tab,
            'projects'         => $model->getByUserAndStatus($userId, $tab),
            'activeCount'      => $model->countByUserAndStatus($userId, 'active'),
            'completedCount'   => $model->countByUserAndStatus($userId, 'completed'),
        ]);
    }
}
