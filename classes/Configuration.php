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
        $this->mail_host = getenv('MAIL_HOST');
        $this->mail_function = getenv('MAIL_FUNCTION');
        $this->mail_smtpauth = getenv('MAIL_SMTPAUTH') === 'true';
        $this->mail_username = getenv('MAIL_USERNAME');
        $this->mail_password = getenv('MAIL_PASSWORD');
        $this->mail_smtpsecure = getenv('MAIL_SMTPSECURE');
        $this->mail_port = getenv('MAIL_PORT');
        $this->covid_mode = getenv('COVID_MODE') === 'false';
        $this->flickr_api_key = getenv('FLICKR_API_KEY');
        $this->proxy_url = getenv('PROXY_URL');
        $this->seeding_tournament_week = getenv('SEEDING_TOURNAMENT_WEEK');
    }
}
