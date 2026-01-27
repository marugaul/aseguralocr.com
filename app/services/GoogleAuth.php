<?php
/**
 * Google OAuth Service
 * Handles Google OAuth authentication for client dashboard
 */

class GoogleAuth {
    private $clientId;
    private $clientSecret;
    private $redirectUri;
    private $scopes = [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile'
    ];

    public function __construct() {
        $config = $this->loadConfig();
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
    }

    /**
     * Load Google OAuth configuration
     */
    private function loadConfig(): array {
        // Try to load from database first
        try {
            require_once __DIR__ . '/../../includes/db.php';
            global $pdo;

            $stmt = $pdo->query("SELECT client_id, client_secret, redirect_uri, scopes
                                 FROM oauth_settings
                                 WHERE provider = 'google' AND is_active = TRUE
                                 LIMIT 1");
            $config = $stmt->fetch();

            if ($config) {
                if (!empty($config['scopes'])) {
                    $this->scopes = json_decode($config['scopes'], true) ?: $this->scopes;
                }
                return $config;
            }
        } catch (Exception $e) {
            // Fall through to config file
        }

        // Fallback to config file
        $configFile = __DIR__ . '/../config/google_oauth.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }

        return [];
    }

    /**
     * Get Google OAuth authorization URL
     */
    public function getAuthUrl(string $state = ''): string {
        if (empty($state)) {
            $state = bin2hex(random_bytes(16));
            $_SESSION['oauth_state'] = $state;
        }

        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $this->scopes),
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     */
    public function getAccessToken(string $code): ?array {
        $tokenUrl = 'https://oauth2.googleapis.com/token';

        $data = [
            'code' => $code,
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Google OAuth token exchange failed: HTTP {$httpCode}");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get user information from Google
     */
    public function getUserInfo(string $accessToken): ?array {
        $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';

        $ch = curl_init($userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Google userinfo request failed: HTTP {$httpCode}");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Refresh access token
     */
    public function refreshAccessToken(string $refreshToken): ?array {
        $tokenUrl = 'https://oauth2.googleapis.com/token';

        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token'
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Google token refresh failed: HTTP {$httpCode}");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Verify state parameter to prevent CSRF
     */
    public function verifyState(string $state): bool {
        if (empty($_SESSION['oauth_state'])) {
            return false;
        }

        $valid = hash_equals($_SESSION['oauth_state'], $state);
        unset($_SESSION['oauth_state']);

        return $valid;
    }

    /**
     * Create or update client from Google user info
     */
    public function createOrUpdateClient(array $userInfo, array $tokens): ?int {
        require_once __DIR__ . '/../../includes/db.php';
        global $pdo;

        try {
            // Check if client exists by Google ID
            $stmt = $pdo->prepare("SELECT id FROM clients WHERE google_id = ? OR email = ?");
            $stmt->execute([$userInfo['id'], $userInfo['email']]);
            $existing = $stmt->fetch();

            $data = [
                'google_id' => $userInfo['id'],
                'email' => $userInfo['email'],
                'nombre_completo' => $userInfo['name'] ?? '',
                'avatar_url' => $userInfo['picture'] ?? null,
                'email_verified' => !empty($userInfo['verified_email']) ? 1 : 0,
                'google_access_token' => $tokens['access_token'] ?? null,
                'google_refresh_token' => $tokens['refresh_token'] ?? null,
                'last_login' => date('Y-m-d H:i:s')
            ];

            if ($existing) {
                // Update existing client
                $sql = "UPDATE clients SET
                        google_id = :google_id,
                        email = :email,
                        nombre_completo = COALESCE(NULLIF(nombre_completo, ''), :nombre_completo),
                        avatar_url = :avatar_url,
                        email_verified = :email_verified,
                        google_access_token = :google_access_token,
                        google_refresh_token = COALESCE(:google_refresh_token, google_refresh_token),
                        last_login = :last_login,
                        status = 'active'
                        WHERE id = :id";

                $data['id'] = $existing['id'];
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

                return (int)$existing['id'];

            } else {
                // Create new client
                $sql = "INSERT INTO clients (google_id, email, nombre_completo, avatar_url,
                        email_verified, google_access_token, google_refresh_token, last_login, status)
                        VALUES (:google_id, :email, :nombre_completo, :avatar_url,
                        :email_verified, :google_access_token, :google_refresh_token, :last_login, 'active')";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

                return (int)$pdo->lastInsertId();
            }

        } catch (Exception $e) {
            error_log("Error creating/updating client: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if Google OAuth is configured
     */
    public function isConfigured(): bool {
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->redirectUri);
    }
}
