<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

class Configuration
{
    public string $mail_host;
    public string $mail_function;
    public bool $mail_smtpauth;
    public string $mail_username;
    public string $mail_password;
    public string $mail_smtpsecure;
    public int $mail_port;
    public bool $covid_mode;
    public string $proxy_url;
    public string $flickr_api_key;
    public string $seeding_tournament_week;

    /**
     */
    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        $dotenv->required([
            'MAIL_HOST',
            'MAIL_FUNCTION',
            'MAIL_SMTPAUTH',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_SMTPSECURE',
            'MAIL_PORT',
            'COVID_MODE',
            'FLICKR_API_KEY',
            'PROXY_URL',
        ]);
        $this->mail_host = $_ENV['MAIL_HOST'] ?? '';
        $this->mail_function = $_ENV['MAIL_FUNCTION'] ?? 'mail';
        $this->mail_smtpauth = ($_ENV['MAIL_SMTPAUTH'] ?? 'false') === 'true';
        $this->mail_username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->mail_password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->mail_smtpsecure = $_ENV['MAIL_SMTPSECURE'] ?? '';
        $this->mail_port = (int)($_ENV['MAIL_PORT'] ?? 25);
        $this->covid_mode = ($_ENV['COVID_MODE'] ?? 'false') === 'true';
        $this->flickr_api_key = $_ENV['FLICKR_API_KEY'] ?? '';
        $this->proxy_url = $_ENV['PROXY_URL'] ?? '';
        $this->seeding_tournament_week = $_ENV['SEEDING_TOURNAMENT_WEEK'] ?? '';
    }
}
