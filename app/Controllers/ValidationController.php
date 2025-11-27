<?php

require_once __DIR__ . '/../Models/User.php';
class ValidationController {
    private $userModel;

    public function __construct() {
        // Assuming User model can be instantiated to check for existing users
        $this->userModel = new User();
    }

    public function checkUserExists() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['exists' => false, 'error' => 'Invalid request method.']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $excludeId = isset($data['excludeUserId']) ? $data['excludeUserId'] : null;

        if (isset($data['username'])) {
            $username = $data['username'];
            $user = $this->userModel->findByUsername($username, $excludeId);
            echo json_encode(['exists' => !empty($user)]);
        } elseif (isset($data['email'])) {
            $email = $data['email'];
            $user = $this->userModel->findByEmail($email, $excludeId);
            echo json_encode(['exists' => !empty($user)]);
        } else {
            echo json_encode(['exists' => false, 'error' => 'No username or email provided.']);
        }
    }
}