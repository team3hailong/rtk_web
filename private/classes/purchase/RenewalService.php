<?php
// RenewalService.php
// Tách xử lý database cho trang renewal
require_once PROJECT_ROOT_PATH . '/private/classes/Database.php';
require_once PROJECT_ROOT_PATH . '/private/classes/RtkAccount.php';
require_once PROJECT_ROOT_PATH . '/private/classes/Package.php';

class RenewalService {
    private $db;
    private $rtkAccount;
    private $packageObj;

    public function __construct() {
        $this->db = new Database();
        $this->rtkAccount = new RtkAccount($this->db);
        $this->packageObj = new Package();
    }

    public function getAccountsByIdsForRenewal($user_id, $selected_accounts) {
        return $this->rtkAccount->getAccountsByIdsForRenewal($user_id, $selected_accounts);
    }

    public function getAllPackagesForRenewal() {
        return $this->packageObj->getAllPackagesForRenewal();
    }

    public function close() {
        $this->db->close();
        $this->packageObj->closeConnection();
    }
}
