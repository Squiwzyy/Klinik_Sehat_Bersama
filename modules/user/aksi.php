<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
check_role('admin');

// Cek duplikat username
function cek_username($pdo, $username, $exclude_id = 0) {
    $stmt = $pdo->prepare("SELECT id_user FROM users WHERE username = :u AND id_user != :id");
    $stmt->execute([':u' => $username, ':id' => $exclude_id]);
    return $stmt->fetch() ? true : false;
}

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    verify_csrf();
    
    if ($action === 'tambah') {
        $username = trim($_POST['username']);
        if (cek_username($pdo, $username)) {
            set_flash('error', 'Username sudah digunakan.');
            redirect('tambah.php');
        }
        
        try {
            $pdo->beginTransaction();
            
            // Insert User
            $stmt = $pdo->prepare("INSERT INTO users (nama, username, password, role) VALUES (:n, :u, :p, :r)");
            $stmt->execute([
                ':n' => trim($_POST['nama']),
                ':u' => $username,
                ':p' => password_hash($_POST['password'], PASSWORD_BCRYPT),
                ':r' => $_POST['role']
            ]);
            $id_user = $pdo->lastInsertId();
            
            // Insert Dokter
            if ($_POST['role'] === 'dokter') {
                $stmt_dok = $pdo->prepare("INSERT INTO dokter (nama_dokter, spesialisasi, no_sip, jadwal_praktek, id_user) VALUES (:n, :s, :sip, :jdw, :id)");
                $stmt_dok->execute([
                    ':n'   => trim($_POST['nama']),
                    ':s'   => trim($_POST['spesialisasi']),
                    ':sip' => trim($_POST['no_sip']),
                    ':jdw' => trim($_POST['jadwal_praktek'] ?? ''),
                    ':id'  => $id_user
                ]);
            }
            
            $pdo->commit();
            set_flash('success', 'User berhasil ditambahkan.');
            redirect('index.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal: ' . $e->getMessage());
            redirect('tambah.php');
        }
    }
    
    if ($action === 'edit') {
        $id = (int)$_POST['id_user'];
        $username = trim($_POST['username']);
        $new_role = $_POST['role'];
        $old_role = $_POST['old_role'];
        
        if (cek_username($pdo, $username, $id)) {
            set_flash('error', 'Username sudah digunakan.');
            redirect("edit.php?id=$id");
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update User
            $params = [
                ':n' => trim($_POST['nama']),
                ':u' => $username,
                ':r' => $new_role,
                ':id' => $id
            ];
            
            $q = "UPDATE users SET nama=:n, username=:u, role=:r";
            if (!empty($_POST['password'])) {
                $q .= ", password=:p";
                $params[':p'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
            }
            $q .= " WHERE id_user=:id";
            $pdo->prepare($q)->execute($params);
            
            // Handle role change logic
            if ($old_role === 'dokter' && $new_role !== 'dokter') {
                $pdo->prepare("DELETE FROM dokter WHERE id_user = :id")->execute([':id' => $id]);
            } elseif ($old_role !== 'dokter' && $new_role === 'dokter') {
                $stmt_dok = $pdo->prepare("INSERT INTO dokter (nama_dokter, spesialisasi, no_sip, jadwal_praktek, id_user) VALUES (:n, :s, :sip, :jdw, :id)");
                $stmt_dok->execute([
                    ':n'   => trim($_POST['nama']),
                    ':s'   => trim($_POST['spesialisasi']),
                    ':sip' => trim($_POST['no_sip']),
                    ':jdw' => trim($_POST['jadwal_praktek'] ?? ''),
                    ':id'  => $id
                ]);
            } elseif ($old_role === 'dokter' && $new_role === 'dokter') {
                $stmt_dok = $pdo->prepare("UPDATE dokter SET nama_dokter=:n, spesialisasi=:s, no_sip=:sip, jadwal_praktek=:jdw WHERE id_user=:id");
                $stmt_dok->execute([
                    ':n'   => trim($_POST['nama']),
                    ':s'   => trim($_POST['spesialisasi']),
                    ':sip' => trim($_POST['no_sip']),
                    ':jdw' => trim($_POST['jadwal_praktek'] ?? ''),
                    ':id'  => $id
                ]);
            }
            
            $pdo->commit();
            set_flash('success', 'Data user berhasil diperbarui.');
            redirect('index.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash('error', 'Gagal: ' . $e->getMessage());
            redirect("edit.php?id=$id");
        }
    }
}

// GET actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    // Toggle active status
    if ($action === 'toggle') {
        $id = (int)$_GET['id'];
        $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id_user = :id AND id_user != :uid")->execute([
            ':id' => $id,
            ':uid' => $_SESSION['user_id']
        ]);
        set_flash('success', 'Status user berhasil diubah.');
        redirect('index.php');
    }
}

redirect('index.php');
