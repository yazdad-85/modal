<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['ip_address', 'email', 'attempted_at'];
    protected $useTimestamps    = false;

    public function tooManyAttempts(string $ip, string $email, int $max = 5, int $minutes = 15): bool
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        $count = $this->where('ip_address', $ip)
            ->where('email', $email)
            ->where('attempted_at >=', $since)
            ->countAllResults();

        return $count >= $max;
    }

    public function record(string $ip, string $email): void
    {
        $this->insert([
            'ip_address'   => $ip,
            'email'        => $email,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
